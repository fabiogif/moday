-- Script para atualizar eventos existentes com cores padrão baseadas no tipo
-- Execute este script APÓS rodar a migration add_color_to_events_table

-- Atualizar eventos do tipo 'promocao' com cor verde
UPDATE events 
SET color = '#10b981' 
WHERE type = 'promocao' AND (color IS NULL OR color = '#3b82f6');

-- Atualizar eventos do tipo 'aviso' com cor amarela/laranja
UPDATE events 
SET color = '#f59e0b' 
WHERE type = 'aviso' AND (color IS NULL OR color = '#3b82f6');

-- Atualizar eventos do tipo 'outro' com cor azul (padrão)
UPDATE events 
SET color = '#3b82f6' 
WHERE type = 'outro' AND (color IS NULL OR color = '#3b82f6');

-- Verificar a atualização
SELECT 
    type,
    color,
    COUNT(*) as total
FROM events
GROUP BY type, color
ORDER BY type, color;

