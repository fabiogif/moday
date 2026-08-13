# 🧪 Plano de Testes - Redis Resilience

## 📋 Casos de Teste Unitários

### 1. RedisHelperTest

#### Teste 1.1: Detectar Redis Disponível
```php
test_redis_helper_detects_available_redis()
```
**Objetivo**: Verificar se RedisHelper detecta corretamente quando Redis está disponível
**Pré-condição**: Redis rodando
**Resultado esperado**: `isAvailable()` retorna `true`

#### Teste 1.2: Retornar Drivers Corretos
```php
test_redis_helper_returns_correct_drivers_when_available()
```
**Objetivo**: Verificar drivers retornados quando Redis disponível
**Resultado esperado**: 
- Cache driver = 'redis'
- Queue driver = 'redis'  
- Session driver = 'redis'
- Broadcast driver = 'reverb'

#### Teste 1.3: Reset de Cache
```php
test_redis_helper_reset()
```
**Objetivo**: Verificar que reset limpa cache de disponibilidade
**Resultado esperado**: Após reset, verifica novamente

---

### 2. HybridRateLimiterTest

#### Teste 2.1: Incrementar Tentativas
```php
test_can_increment_attempts()
```
**Objetivo**: Verificar incremento correto de tentativas
**Passos**:
1. Hit 1 → retorna 1
2. Hit 2 → retorna 2
**Resultado esperado**: Contagem correta

#### Teste 2.2: Detectar Limite Excedido
```php
test_detects_too_many_attempts()
```
**Objetivo**: Verificar detecção de limite excedido
**Passos**:
1. Fazer 3 hits
2. Verificar `tooManyAttempts(key, 3)`
**Resultado esperado**: `true`

#### Teste 2.3: Tentativas Restantes
```php
test_retrieves_retries_left()
```
**Objetivo**: Calcular tentativas restantes corretamente
**Passos**:
1. Fazer 2 hits
2. Verificar `retriesLeft(key, 5)`
**Resultado esperado**: 3

#### Teste 2.4: Limpar Tentativas
```php
test_can_clear_attempts()
```
**Objetivo**: Verificar limpeza de tentativas
**Passos**:
1. Fazer 2 hits
2. Chamar `clear(key)`
3. Verificar `attempts(key)`
**Resultado esperado**: 0

#### Teste 2.5: Cleanup de Expirados
```php
test_cleanup_removes_expired_records()
```
**Objetivo**: Remover registros expirados do database
**Passos**:
1. Inserir registro expirado
2. Chamar `cleanup()`
3. Verificar que foi removido
**Resultado esperado**: Registro deletado

---

### 3. HybridSessionHandlerTest

#### Teste 3.1: Escrever e Ler Sessão
```php
test_can_write_and_read_session()
```
**Objetivo**: Verificar gravação e leitura básica
**Passos**:
1. Write(sessionId, data)
2. Read(sessionId)
**Resultado esperado**: Data retornada corretamente

#### Teste 3.2: Gravar no Database
```php
test_writes_to_database()
```
**Objetivo**: Verificar que sessão é salva no database
**Passos**:
1. Write(sessionId, data)
2. Query database
**Resultado esperado**: Registro existe

#### Teste 3.3: Destruir Sessão
```php
test_can_destroy_session()
```
**Objetivo**: Verificar destruição completa
**Passos**:
1. Write(sessionId, data)
2. Destroy(sessionId)
3. Read(sessionId)
4. Query database
**Resultado esperado**: Sessão removida de tudo

#### Teste 3.4: Garbage Collection
```php
test_garbage_collection_removes_old_sessions()
```
**Objetivo**: Remover sessões antigas
**Passos**:
1. Inserir sessão antiga (last_activity < maxlifetime)
2. Chamar gc(maxlifetime)
3. Verificar que foi removida
**Resultado esperado**: Sessão deletada

#### Teste 3.5: Sessão Inexistente
```php
test_returns_empty_for_non_existent_session()
```
**Objetivo**: Retornar vazio para sessão inexistente
**Passos**:
1. Read('non_existent')
**Resultado esperado**: String vazia

---

## 🔧 Casos de Teste de Integração

### 4. RedisResilienceTest

#### Teste 4.1: Sistema com Redis Disponível
```php
test_system_works_with_redis_available()
```
**Objetivo**: Verificar funcionamento normal
**Pré-condição**: Redis rodando
**Passos**:
1. GET /api/health
2. GET /api/plans
**Resultado esperado**: 200 OK em ambas

#### Teste 4.2: Sessões no Database
```php
test_sessions_are_backed_up_to_database()
```
**Objetivo**: Verificar backup de sessões
**Passos**:
1. Criar usuário
2. POST /api/auth/login
3. Query table sessions
**Resultado esperado**: Sessão existe no database

#### Teste 4.3: Rate Limiting com Database
```php
test_rate_limiting_works_with_database()
```
**Objetivo**: Verificar rate limiting em database
**Passos**:
1. Fazer 5 hits
2. Verificar `tooManyAttempts(key, 3)`
3. Query table rate_limits
**Resultado esperado**: 
- tooManyAttempts = true
- Registro existe no database

#### Teste 4.4: Comando Health Check
```php
test_redis_health_check_command()
```
**Objetivo**: Verificar comando de health check
**Passos**:
1. Artisan call redis:health
**Resultado esperado**: Exit code correto

#### Teste 4.5: Comando Cleanup Rate Limits
```php
test_rate_limits_cleanup_command()
```
**Objetivo**: Verificar limpeza de rate limits
**Passos**:
1. Inserir registro expirado
2. Artisan call rate-limits:cleanup
3. Verificar que foi removido
**Resultado esperado**: Registro deletado

#### Teste 4.6: Comando Cleanup Sessões
```php
test_sessions_cleanup_command()
```
**Objetivo**: Verificar limpeza de sessões
**Passos**:
1. Inserir sessão antiga
2. Artisan call sessions:cleanup
3. Verificar que foi removida
**Resultado esperado**: Sessão deletada

---

## 🎭 Casos de Teste Manual

### 5. Testes de Resiliência (Manual)

#### Teste 5.1: Fallback de Sessão
**Objetivo**: Usuário não é deslogado quando Redis cai

**Passos**:
1. Iniciar aplicação com Redis rodando
2. Fazer login via frontend/API
3. Verificar que está autenticado
4. **Derrubar Redis**: `docker stop redis`
5. Fazer requisição autenticada (ex: GET /api/auth/me)
6. Verificar que ainda está autenticado
7. **Ligar Redis**: `docker start redis`
8. Fazer nova requisição autenticada
9. Verificar que continua autenticado

**Resultado esperado**: 
- ✅ Permanece autenticado todo o tempo
- ✅ Nenhum erro visível para usuário
- ⚠️ Logs mostram fallback ativado

---

#### Teste 5.2: Fallback de Rate Limiting
**Objetivo**: Rate limiting continua funcionando sem Redis

**Passos**:
1. Configurar rate limit baixo (ex: 5 req/min) em uma rota
2. Fazer 3 requisições (OK)
3. **Derrubar Redis**
4. Fazer mais 3 requisições
5. Verificar que 6ª requisição é bloqueada (429 Too Many Requests)

**Resultado esperado**:
- ✅ Rate limiting funciona sem Redis
- ✅ Retorna 429 após limite
- ⚠️ Pode ser ~20-30ms mais lento

---

#### Teste 5.3: Fallback de Queue
**Objetivo**: Jobs não são perdidos quando Redis cai

**Passos**:
1. Disparar um job
2. Verificar que foi processado
3. **Derrubar Redis**
4. Disparar outro job
5. Iniciar queue worker: `php artisan queue:work database`
6. Verificar que job foi processado

**Resultado esperado**:
- ✅ Job enfileirado no database
- ✅ Job processado corretamente
- ✅ Nenhum job perdido

---

#### Teste 5.4: Recovery Automático
**Objetivo**: Sistema volta a usar Redis automaticamente

**Passos**:
1. **Derrubar Redis**
2. Verificar fallback: `php artisan redis:health`
3. Fazer algumas requisições (usam database)
4. **Ligar Redis**
5. Aguardar 5 minutos (ou executar: `php artisan redis:health`)
6. Fazer mais requisições

**Resultado esperado**:
- ✅ Health check detecta Redis voltou
- ✅ Flag de fallback é removida
- ✅ Sistema volta a usar Redis
- ✅ Logs mostram recovery

---

### 6. Testes de Performance

#### Teste 6.1: Latência de Sessão (Redis)
**Objetivo**: Medir latência com Redis

**Passos**:
1. Redis rodando
2. Fazer 100 requisições autenticadas
3. Medir tempo médio

**Resultado esperado**: < 50ms média

---

#### Teste 6.2: Latência de Sessão (Database)
**Objetivo**: Medir latência com Database fallback

**Passos**:
1. Redis parado
2. Fazer 100 requisições autenticadas
3. Medir tempo médio

**Resultado esperado**: < 150ms média

---

#### Teste 6.3: Rate Limiting (Redis)
**Objetivo**: Medir latência de rate limiting

**Passos**:
1. Redis rodando
2. Fazer 1000 requisições
3. Medir overhead do rate limiting

**Resultado esperado**: < 20ms overhead

---

#### Teste 6.4: Rate Limiting (Database)
**Objetivo**: Medir latência com Database

**Passos**:
1. Redis parado
2. Fazer 1000 requisições
3. Medir overhead do rate limiting

**Resultado esperado**: < 80ms overhead

---

### 7. Testes de Stress

#### Teste 7.1: Carga com Redis
**Objetivo**: Verificar sistema sob carga normal

**Ferramenta**: Apache Bench ou k6
```bash
ab -n 10000 -c 100 http://localhost:8000/api/health
```

**Resultado esperado**:
- ✅ Requests/sec > 1000
- ✅ Nenhum erro
- ✅ Latência consistente

---

#### Teste 7.2: Carga sem Redis
**Objetivo**: Verificar sistema sob carga no fallback

**Passos**:
1. Derrubar Redis
2. Executar mesmo teste de carga

**Resultado esperado**:
- ✅ Requests/sec > 700 (70% da performance normal)
- ✅ Nenhum erro crítico
- ✅ Latência 20-30% maior mas estável

---

#### Teste 7.3: Troca Durante Carga
**Objetivo**: Verificar transição sob carga

**Passos**:
1. Iniciar teste de carga (1 minuto)
2. Após 20s, derrubar Redis
3. Após 40s, ligar Redis

**Resultado esperado**:
- ✅ Nenhuma requisição falhada
- ✅ Transição suave
- ✅ Latência aumenta/diminui gradualmente

---

## 📊 Matriz de Cobertura de Testes

| Componente | Unitários | Integração | Manual | Performance |
|------------|-----------|------------|--------|-------------|
| RedisHelper | ✅ 3 | ✅ 1 | ✅ 1 | ✅ 0 |
| HybridRateLimiter | ✅ 5 | ✅ 2 | ✅ 1 | ✅ 2 |
| HybridSessionHandler | ✅ 5 | ✅ 1 | ✅ 1 | ✅ 2 |
| Commands | ✅ 0 | ✅ 3 | ✅ 1 | ✅ 0 |
| Resiliência Geral | ✅ 0 | ✅ 1 | ✅ 1 | ✅ 1 |
| **TOTAL** | **13** | **8** | **5** | **5** |

**Total de casos de teste**: **31**

---

## ✅ Checklist de Execução

### Antes dos Testes
- [ ] Migrations executadas
- [ ] Tabelas criadas (sessions, rate_limits, jobs)
- [ ] Redis rodando
- [ ] Database conectado
- [ ] .env configurado

### Testes Unitários
- [ ] RedisHelperTest (3 testes)
- [ ] HybridRateLimiterTest (5 testes)
- [ ] HybridSessionHandlerTest (5 testes)

### Testes de Integração
- [ ] RedisResilienceTest (6 testes)

### Testes Manuais
- [ ] Fallback de Sessão
- [ ] Fallback de Rate Limiting
- [ ] Fallback de Queue
- [ ] Recovery Automático

### Testes de Performance
- [ ] Latência de Sessão (Redis e Database)
- [ ] Latência de Rate Limiting (Redis e Database)

### Testes de Stress
- [ ] Carga com Redis
- [ ] Carga sem Redis
- [ ] Troca durante carga

### Validação Final
- [ ] Todos os testes passando
- [ ] Performance aceitável
- [ ] Logs sem erros críticos
- [ ] Documentação atualizada

---

## 🎯 Critérios de Aceitação

### Funcional
- ✅ Todos os testes unitários passando (100%)
- ✅ Todos os testes de integração passando (100%)
- ✅ Testes manuais validados

### Performance
- ✅ Latência < 50ms com Redis
- ✅ Latência < 150ms sem Redis
- ✅ Degradação máxima de 30%

### Resiliência
- ✅ Zero downtime quando Redis cai
- ✅ Usuários não deslogados
- ✅ Jobs não perdidos
- ✅ Recovery automático funciona

### Operacional
- ✅ Comandos de cleanup funcionando
- ✅ Health check funcionando
- ✅ Logs informativos
- ✅ Sem memory leaks

---

**Elaborado por**: AI Assistant  
**Data**: 10/03/2026  
**Versão**: 1.0.0
