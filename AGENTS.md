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

 ## Commits

- Antes de cada commit, execute `php artisan route:list` no container `repre-backend-1`.
- Faça commits pequenos, lógicos, com alvo aproximado de 200 linhas; registre shortstat.
- Não faça push nem force remoto.
- Padronização deve ser um commit separado da implementação.
