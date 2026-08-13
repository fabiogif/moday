-- Script de Migração: Roles -> Profiles
-- Execute este script quando o banco de dados estiver disponível

-- =========================================================================
-- PASSO 1: Migrar Roles para Profiles
-- =========================================================================

-- Criar Profiles equivalentes aos Roles existentes (se não existirem)
INSERT INTO profiles (name, slug, description, tenant_id, is_active, created_at, updated_at)
SELECT 
    r.name,
    r.slug,
    r.description,
    r.tenant_id,
    r.is_active,
    r.created_at,
    r.updated_at
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM profiles p 
    WHERE p.slug = r.slug AND p.tenant_id = r.tenant_id
);

-- =========================================================================
-- PASSO 2: Migrar Permissões dos Roles para Profiles
-- =========================================================================

-- Criar associações de permissões para os Profiles (equivalente aos Roles)
INSERT IGNORE INTO permission_profile (permission_id, profile_id, created_at, updated_at)
SELECT 
    rp.permission_id,
    p.id as profile_id,
    NOW(),
    NOW()
FROM role_permissions rp
INNER JOIN roles r ON rp.role_id = r.id
INNER JOIN profiles p ON p.slug = r.slug AND p.tenant_id = r.tenant_id;

-- =========================================================================
-- PASSO 3: Migrar Associações de Usuários
-- =========================================================================

-- Associar usuários aos Profiles (equivalente aos Roles que eles tinham)
INSERT IGNORE INTO user_profiles (user_id, profile_id, created_at, updated_at)
SELECT 
    ru.user_id,
    p.id as profile_id,
    NOW(),
    NOW()
FROM role_user ru
INNER JOIN roles r ON ru.role_id = r.id
INNER JOIN profiles p ON p.slug = r.slug AND p.tenant_id = r.tenant_id;

-- =========================================================================
-- PASSO 4: Verificação dos Dados Migrados
-- =========================================================================

-- Verificar quantos Profiles foram criados
SELECT 
    'Profiles criados a partir de Roles' as descricao,
    COUNT(*) as total
FROM profiles p
WHERE EXISTS (
    SELECT 1 FROM roles r 
    WHERE p.slug = r.slug AND p.tenant_id = r.tenant_id
);

-- Verificar quantas permissões foram migradas
SELECT 
    'Permissões migradas para Profiles' as descricao,
    COUNT(*) as total
FROM permission_profile;

-- Verificar quantos usuários foram associados aos Profiles
SELECT 
    'Usuários associados aos Profiles' as descricao,
    COUNT(*) as total
FROM user_profiles;

-- Listar Profiles criados
SELECT 
    p.id,
    p.name as profile_name,
    p.slug as profile_slug,
    p.tenant_id,
    COUNT(DISTINCT up.user_id) as total_usuarios,
    COUNT(DISTINCT pp.permission_id) as total_permissoes
FROM profiles p
LEFT JOIN user_profiles up ON p.id = up.profile_id
LEFT JOIN permission_profile pp ON p.id = pp.profile_id
GROUP BY p.id
ORDER BY p.tenant_id, p.name;

-- =========================================================================
-- NOTAS IMPORTANTES
-- =========================================================================
-- 1. Faça backup do banco antes de executar este script
-- 2. Execute em ambiente de desenvolvimento primeiro
-- 3. Verifique os dados após cada passo
-- 4. NÃO delete os dados de Roles até ter certeza que tudo funciona
-- 5. Os métodos hasRole() estão deprecated mas ainda funcionam (fallback para hasProfile)
