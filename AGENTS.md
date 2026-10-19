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

## Regras desta etapa

- Estas regras prevalecem sobre as seções 1, 3 e 4 quando houver conflito.
- Faça perguntas somente sobre negócio; não crie relatórios nem leia ou escreva `maestri/` ou `.maestri/`.
- Fase2 não executa suíte; padronização é permitida e separada das funcionalidades.
- Use commits simples em português, sem prefixos, como `Adiciona novo controller` ou `Corrige autenticacao`.
- Mantenha commits próximos de 200 linhas, informe shortstat e execute `route:list` antes de cada commit.
- Não crie rotas sem classes existentes e não reescreva histórico remoto.

## Padrões da API

- URLs são plurais kebab-case; parâmetros são singulares snake_case com `whereUuid`, sem regex manual.
- Use `apiResource` para CRUD; rotas manuais ficam para auth, membros e desempenho.
- Auth usa `register`, `login`, `logout`, `me`, `forgot-password` e `reset-password`.
- `SchoolClassController` é singular e usa `index`, `store`, `show`, `update`, `destroy`.
- Use FormRequest por payload, com regras próprias para POST e PATCH; policies autorizam no servidor.
- Resources usam envelope `data`; paginação mantém `links`/`meta`, padrão 20 e máximo 100.
- Erros usam `message`; validação usa `errors` com 422; preserve 200, 201, 204, 401, 403, 404, 409 e 429, e 500 genérico em produção.
- Models usam PascalCase singular; tabelas snake_case plural; IDs e FKs UUID.
- Use `date` para nascimento, `starts_on` e `ends_on`; prazo, evento e `completed_at` usam timestamps consistentes.
- Policies têm métodos Laravel que retornam bool.
- Não adicione comentários ao código API, incluindo comentários herdados; essa regra não se aplica a frontend ou vendor.
