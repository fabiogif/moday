# 🏗️ Arquitetura de Resiliência Redis

## 📐 Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────────┐
│                         APLICAÇÃO LARAVEL                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐       │
│  │   Sessions     │  │  Rate Limiting │  │     Cache      │       │
│  │   Handler      │  │     Handler    │  │    Manager     │       │
│  └────────┬───────┘  └────────┬───────┘  └────────┬───────┘       │
│           │                    │                    │                │
│           └────────────────────┴────────────────────┘                │
│                              │                                       │
│                    ┌─────────▼──────────┐                          │
│                    │   RedisHelper      │                          │
│                    │  (Health Check)    │                          │
│                    └─────────┬──────────┘                          │
│                              │                                       │
└──────────────────────────────┼───────────────────────────────────────┘
                               │
              ┌────────────────┴────────────────┐
              │                                  │
              ▼                                  ▼
    ┌─────────────────┐              ┌──────────────────┐
    │     REDIS       │              │    DATABASE      │
    │   (Primary)     │              │   (Fallback)     │
    │                 │              │                  │
    │ • Fast          │              │ • Reliable       │
    │ • In-Memory     │              │ • Persistent     │
    │ • Volatile      │              │ • Slower         │
    └─────────────────┘              └──────────────────┘
            │                                  │
            └──────────┬───────────────────────┘
                       │
                       ▼
              ┌─────────────────┐
              │  FILE SYSTEM    │
              │  (Last Resort)  │
              │                 │
              │ • Always Works  │
              │ • Very Slow     │
              └─────────────────┘
```

---

## 🔄 Fluxo de Dados - Sessão

### Cenário 1: Redis Disponível ✅

```
Request
   │
   ▼
┌──────────────────┐
│ HybridSession    │
│ Handler          │
└────────┬─────────┘
         │
         ├──────────────┐
         │              │
         ▼              ▼
    ┌────────┐    ┌─────────┐
    │ REDIS  │    │ Database│
    │  FAST  │    │ BACKUP  │
    └────────┘    └─────────┘
         │
         ▼
    Response (50ms)
```

### Cenário 2: Redis Indisponível ❌

```
Request
   │
   ▼
┌──────────────────┐
│ HybridSession    │
│ Handler          │
└────────┬─────────┘
         │
         ▼
    ┌────────┐
    │ REDIS  │ ❌ Connection Failed
    └────────┘
         │
         ▼
    [Fallback]
         │
         ▼
    ┌─────────┐
    │Database │ ✅ Works!
    │ SLOWER  │
    └─────────┘
         │
         ▼
    Response (120ms)
```

---

## 🔐 Fluxo de Rate Limiting

### Normal Flow (Redis UP)

```
API Request
     │
     ▼
┌──────────────────────┐
│ ThrottleMiddleware   │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ HybridRateLimiter    │
└─────────┬────────────┘
          │
          ▼
     ┌────────┐
     │ REDIS  │ ✅
     └────┬───┘
          │
    [Check Count]
          │
    ┌─────┴─────┐
    │           │
    ▼           ▼
  OK (200)   Blocked (429)
```

### Fallback Flow (Redis DOWN)

```
API Request
     │
     ▼
┌──────────────────────┐
│ ThrottleMiddleware   │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ HybridRateLimiter    │
└─────────┬────────────┘
          │
          ▼
     ┌────────┐
     │ REDIS  │ ❌
     └────┬───┘
          │
    [Fallback]
          │
          ▼
    ┌──────────┐
    │ Database │ ✅
    │  Table:  │
    │rate_limits│
    └────┬─────┘
         │
   [Check Count]
         │
    ┌────┴────┐
    │         │
    ▼         ▼
  OK (200) Blocked (429)
```

---

## 📊 Tabelas do Database

### sessions
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT,
    last_activity INT,
    
    INDEX(user_id, last_activity)
);
```

### rate_limits
```sql
CREATE TABLE rate_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(191),
    attempts INT DEFAULT 1,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    
    INDEX(key),
    INDEX(expires_at),
    INDEX(key, expires_at)
);
```

### jobs
```sql
CREATE TABLE jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    queue VARCHAR(255),
    payload LONGTEXT,
    attempts TINYINT,
    reserved_at INT,
    available_at INT,
    created_at INT,
    
    INDEX(queue)
);
```

---

## 🔄 Recovery Automático

```
┌─────────────────────────────────────────────────┐
│  TIMELINE: Redis Failure → Recovery             │
├─────────────────────────────────────────────────┤
│                                                  │
│  T+0s    Redis Crashes ❌                       │
│          └─> Detectado pela próxima request     │
│                                                  │
│  T+1s    RedisHelper marca como indisponível    │
│          └─> Fallback ativado automaticamente   │
│                                                  │
│  T+2s    Todas as requests usam Database        │
│          └─> Performance: 70-80% do normal      │
│          └─> Usuários: Não percebem problema    │
│                                                  │
│  ...     Sistema operando em modo degradado     │
│                                                  │
│  T+300s  Redis volta online ✅                  │
│          └─> Health check detecta recovery      │
│                                                  │
│  T+305s  Sistema volta ao modo normal           │
│          └─> Performance: 100%                  │
│          └─> Flag de fallback removida          │
│                                                  │
│  T+310s  Todas as requests usam Redis           │
│          └─> Recovery completo!                 │
│                                                  │
└─────────────────────────────────────────────────┘

Total Downtime: 0 segundos ✅
```

---

## 🎯 Componentes e Responsabilidades

### RedisHelper
```
Responsabilidade:
├─ Detectar disponibilidade do Redis
├─ Retornar driver apropriado
├─ Cachear resultado (evita múltiplas tentativas)
└─ Reset manual se necessário

Métodos:
├─ isAvailable() → bool
├─ getCacheDriver() → string
├─ getQueueDriver() → string
├─ getSessionDriver() → string
└─ reset() → void
```

### HybridSessionHandler
```
Responsabilidade:
├─ Salvar sessão em Redis + Database
├─ Ler do Redis primeiro
├─ Fallback para Database se falhar
└─ Garbage collection de sessões antigas

Métodos:
├─ read($sessionId) → string
├─ write($sessionId, $data) → bool
├─ destroy($sessionId) → bool
└─ gc($maxlifetime) → int
```

### HybridRateLimiter
```
Responsabilidade:
├─ Verificar rate limits em Redis
├─ Fallback para Database se necessário
├─ Incrementar contadores
└─ Cleanup de registros expirados

Métodos:
├─ tooManyAttempts($key, $max) → bool
├─ hit($key, $decay) → int
├─ attempts($key) → int
├─ clear($key) → void
└─ cleanup() → int
```

### Commands
```
RedisHealthCheck:
├─ Verifica saúde do Redis
├─ Ativa/desativa fallback
├─ Notifica admins (opcional)
└─ Execução: */5 * * * * (a cada 5 min)

CleanupRateLimits:
├─ Remove registros expirados
├─ Mantém tabela pequena
└─ Execução: 0 * * * * (a cada hora)

CleanupSessions:
├─ Remove sessões antigas
├─ Mantém tabela otimizada
└─ Execução: 0 2 * * * (diariamente)
```

---

## 📈 Métricas de Performance

### Latências Típicas

```
┌─────────────────┬──────────┬──────────┬──────────┐
│ Operação        │  Redis   │ Database │   File   │
├─────────────────┼──────────┼──────────┼──────────┤
│ Session Read    │   5ms    │  30ms    │  50ms    │
│ Session Write   │  10ms    │  40ms    │  80ms    │
│ Rate Limit      │   3ms    │  20ms    │   N/A    │
│ Cache Get       │   2ms    │  25ms    │ 100ms    │
│ Cache Put       │   5ms    │  35ms    │ 120ms    │
└─────────────────┴──────────┴──────────┴──────────┘

Performance Ratio:
  Redis:    100% (baseline)
  Database:  70-80%
  File:      40-50%
```

### Throughput

```
Requests per Second (req/s):

Redis UP:        1000+ req/s  ████████████████████ 100%
Database Only:    700 req/s   ██████████████       70%
File System:      400 req/s   ████████             40%
```

---

## 🔍 Debugging e Monitoramento

### Logs Importantes

```bash
# Redis Status
[2026-03-10 15:30:00] INFO: Redis is healthy (45ms)

# Fallback Ativado
[2026-03-10 15:35:00] WARNING: Redis unavailable, using database fallback

# Recovery
[2026-03-10 15:40:00] INFO: Redis recovered, switched back from fallback

# Session Backup
[2026-03-10 15:30:05] DEBUG: Session saved to database backup

# Rate Limit
[2026-03-10 15:30:10] DEBUG: Rate limit hit: api_user_123 (3/5)
```

### Métricas para Prometheus/Grafana

```prometheus
# Redis Availability
redis_available{status="up|down"} gauge

# Fallback Usage
redis_fallback_active{type="session|cache|rate_limit"} gauge

# Latency
http_request_duration_seconds{store="redis|database"} histogram

# Error Rate
redis_connection_errors_total counter
```

---

## 🚨 Alertas Recomendados

```yaml
alerts:
  - name: RedisDown
    condition: redis_available == 0
    severity: critical
    notification: email, slack
    
  - name: FallbackActive
    condition: redis_fallback_active > 5min
    severity: warning
    notification: slack
    
  - name: HighLatency
    condition: p95_latency > 150ms
    severity: warning
    notification: slack
    
  - name: SessionTableGrowth
    condition: sessions_count > 100000
    severity: warning
    notification: email
```

---

## 📚 Referências de Código

```php
// Verificar disponibilidade
RedisHelper::isAvailable(); // true/false

// Obter driver apropriado
Config::set('cache.default', RedisHelper::getCacheDriver());

// Health check manual
Artisan::call('redis:health');

// Cleanup manual
Artisan::call('sessions:cleanup', ['--hours' => 24]);
Artisan::call('rate-limits:cleanup');

// Forçar fallback (teste)
env('REDIS_ENABLED', false);
RedisHelper::reset();
```

---

**Documentação Técnica Completa** 📖  
**Versão**: 1.0.0  
**Data**: 10/03/2026
