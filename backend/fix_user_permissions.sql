-- Script para atribuir todas as permissões ao usuário de teste
-- Execute este script no seu banco de dados MySQL

-- Verificar o usuário e tenant
SELECT id, name, email, tenant_id FROM users WHERE email = 'teste@example.com';

-- Verificar permissões existentes
SELECT id, name, slug FROM permissions WHERE tenant_id = (SELECT tenant_id FROM users WHERE email = 'teste@example.com' LIMIT 1);

-- Atribuir todas as permissões ao usuário diretamente
INSERT IGNORE INTO user_permissions (user_id, permission_id, created_at, updated_at)
SELECT 
    u.id,
    p.id,
    NOW(),
    NOW()
FROM users u
CROSS JOIN permissions p
WHERE u.email = 'teste@example.com'
AND p.tenant_id = u.tenant_id;

-- Verificar permissões atribuídas
SELECT 
    u.name as usuario,
    u.email,
    COUNT(up.permission_id) as total_permissoes
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
WHERE u.email = 'teste@example.com'
GROUP BY u.id, u.name, u.email;

-- Listar permissões específicas do módulo users
SELECT p.name, p.slug 
FROM permissions p
INNER JOIN user_permissions up ON p.id = up.permission_id
INNER JOIN users u ON up.user_id = u.id
WHERE u.email = 'teste@example.com'
AND p.slug LIKE 'users.%'
ORDER BY p.slug;
