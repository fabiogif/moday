#!/usr/bin/env node

/**
 * Script para remover // console.log de produção
 * Mantém apenas console.error em blocos catch
 */

const fs = require('fs')
const path = require('path')

const srcDir = path.join(__dirname, 'src')

// Arquivos a processar (principais da aplicação)
const filesToProcess = [
  'app/admin/plans/[id]/page.tsx',
  'app/(dashboard)/settings/delivery/page.tsx',
  'lib/api-client.ts',
  'hooks/use-authenticated-api.ts',
  'app/landing/components/pricing-section.tsx',
  'app/(dashboard)/products/[id]/edit/page.tsx',
  'app/(dashboard)/products/new/page.tsx',
].map(f => path.join(srcDir, f))

function removeConsoleLogs(filePath) {
  if (!fs.existsSync(filePath)) {
    // console.log(`❌ Arquivo não encontrado: ${filePath}`)
    return
  }

  let content = fs.readFileSync(filePath, 'utf8')
  const originalContent = content
  
  // Remover // console.log
  content = content.replace(/\s*console\.log\([^)]*\);?\n?/g, '')
  
  // Remover console.debug
  content = content.replace(/\s*console\.debug\([^)]*\);?\n?/g, '')
  
  // Remover console.info
  content = content.replace(/\s*console\.info\([^)]*\);?\n?/g, '')
  
  // Remover console.warn (opcional)
  content = content.replace(/\s*console\.warn\([^)]*\);?\n?/g, '')
  
  // Remover blocos de console.group/groupEnd
  content = content.replace(/\s*console\.group\([^)]*\);?\n?/g, '')
  content = content.replace(/\s*console\.groupEnd\([^)]*\);?\n?/g, '')
  
  // Remover linhas vazias duplicadas
  content = content.replace(/\n\n\n+/g, '\n\n')
  
  if (content !== originalContent) {
    fs.writeFileSync(filePath, content, 'utf8')
    // console.log(`✅ Limpo: ${path.relative(srcDir, filePath)}`)
  } else {
    // console.log(`⏭️  Sem alterações: ${path.relative(srcDir, filePath)}`)
  }
}

// console.log('🧹 Removendo // console.log de produção...\n')

filesToProcess.forEach(removeConsoleLogs)

// console.log('\n✅ Processo concluído!')
// console.log('\n💡 Nota: console.error em blocos catch foram mantidos para debug de erros')

