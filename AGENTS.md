# AGENTS.md

Diretrizes para agentes neste repositório.

## 1. Pense antes de codar

- Declare suposições e exponha dúvidas antes de implementar.
- Se houver interpretações possíveis, pergunte antes de escolher.
- Prefira a abordagem simples e o menor código necessário.
- Defina critérios verificáveis e confirme cada etapa.

## 2. Simplicidade

- Nenhuma funcionalidade além do pedido.
- Nenhuma abstração especulativa ou tratamento para cenários impossíveis.
- Se 200 linhas podem ser 50, simplifique.

## 3. Mudanças cirúrgicas

- Altere somente linhas diretamente relacionadas ao pedido.
- Preserve estilo e comportamento adjacente.
- Remova apenas imports ou código que sua mudança tornou órfão.
- Não altere `.gitignore`, Obsidian, frontend ou banco sem autorização explícita.

## 4. Execução

- Antes de cada commit, execute `php artisan route:list` no container `repre-backend-1`.
- Faça commits pequenos, lógicos, com alvo aproximado de 200 linhas; registre shortstat.
- Não faça push nem force remoto.
- Padronização deve ser um commit separado da implementação.
- Fase2 não executa testes; use somente lint e `route:list`.
- Até a Fase3, não execute a suíte. Na Fase3, o único comando de testes autorizado é `docker exec -t repre-backend-1 php artisan test`, usando o banco já utilizado por esse container.
- Não crie isolamento, bancos, guards ou scripts novos para testes.
- Cline participa somente da Fase3.
- Não leia nem crie arquivos em `maestri/` ou `.maestri/`, e não crie notas ou relatórios.
- Não copie nem leia segredos `.env`.
- Não escreva código API antes da leitura integral de `backend` solicitada para a Fase1.
- A padronização é permitida quando alinhada ao escopo e deve ser separada da implementação.
- A exceção `AGENTS.md` de padrões de API, limitada a 40 linhas, só pode ser adicionada depois da leitura integral e alinhamento.
