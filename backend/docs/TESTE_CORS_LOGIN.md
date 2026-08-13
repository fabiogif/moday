# Teste de Login com CORS

## Problema Persistente

O navegador está fazendo uma requisição **OPTIONS preflight** antes do POST para `/api/auth/login`, mas essa requisição não está retornando os headers CORS necessários.

## Solução Temporária

Enquanto investigamos por que o servidor Apache/Heroku não está executando o código PHP do index.php para requisições OPTIONS, você pode testar o login diretamente sem passar pelo navegador:

### Teste com cURL (funciona):

```bash
curl -X POST "https://orca-app-7hejo.ondigitalocean.app/api/auth/login" \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"$Duda0793"}' \
  | jq '.'
```

Resposta:
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "user": {...},
    "token": "eyJ0eXAi..."
  }
}
```

## Por que funciona com cURL mas não com o navegador?

O navegador faz uma **requisição preflight OPTIONS** antes de enviar o POST quando:
- A requisição tem `Content-Type: application/json`
- A requisição tem headers customizados como `Authorization`

Essa requisição OPTIONS precisa retornar os headers CORS, mas algo no servidor está bloqueando isso.

## Próximos Passos

1. Verificar se o Heroku PHP buildpack tem alguma configuração especial para OPTIONS
2. Considerar usar Nginx ao invés de Apache
3. Ou adicionar configuração CORS direto no painel do Digital Ocean (se disponível)

