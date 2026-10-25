# API

Base URL: `/api`. Rotas protegidas exigem `Authorization: Bearer <token>`. Entidades usam envelope `data`; coleções paginadas usam `data`, `links` e `meta`. `page` começa em 1 e `per_page` aceita 1..100, padrão 20. Erros usam `message`; validação retorna `422` com `errors`; autenticação `401`; autorização `403`; inexistência `404`; conflito `409`; limite `429`.

## Autenticação

| Método e URL | Acesso | Corpo | Resposta |
|---|---|---|---|
| POST `/auth/register` | público | obrigatório: `name,birthdate,email,password,password_confirmation` | `201 {data:{user,token}}` |
| POST `/auth/login` | público | `email,password` | `200 {data:{user,token}}`; credencial inválida `401` |
| POST `/auth/logout` | Bearer | nenhum | `204` |
| GET `/auth/me` | Bearer | nenhum | `200 {data:{user}}` |
| PUT `/auth/me` | Bearer | opcional: `name,birthdate,email,password,password_confirmation` | `200 {data:{user}}`; senha revoga tokens |
| DELETE `/auth/me` | Bearer | nenhum | `204`; vínculos impeditivos `409` |
| POST `/auth/forgot-password` | público | `email` | `200 {message}` genérico; throttle `429` |
| POST `/auth/reset-password` | público | `token,email,password,password_confirmation` | `200 {message}`; token inválido/reutilizado `422` |

## Recursos CRUD

Todos os IDs são UUID. Os endpoints abaixo aceitam `GET` lista, `GET /{id}`, `POST`, `PUT/PATCH /{id}` e `DELETE /{id}`. POST/PATCH usam os campos indicados; `created_by` e o `user_id` das conclusões vêm do Bearer. Gestores autorizados enviam `role` nos vínculos, e o admin envia `system_role` em usuários.

| Recurso | URL | Campos POST | Campos PATCH |
|---|---|---|---|
| Turmas | `/school-classes` | obrigatórios `name,course,semester,year,starts_on,ends_on`; `ends_on >= starts_on` | opcionais; datas são comparadas com o valor persistido |
| Tarefas | `/tasks` | obrigatórios `title,due_date,class_id`; opcionais `description,priority` | `title,description,priority,due_date` |
| Conclusões | `/task-completions` | obrigatório `task_id`; usuário vem do Bearer | opcional `completed_at` |
| Avisos | `/announcements` | `title,content,class_id` | `title,content` |
| Organizações | `/organizations` | `name,description`; opcional `type` | `name,description,type` |
| Posts | `/posts` | `title,description,organization_id` | `title,description` |
| Eventos | `/calendar-events` | `title,description,starts_at`; opcional `ends_at,class_id` | `title,description,starts_at,ends_at,class_id` |
| Usuários | `/users` | admin: `name,email,birthdate,system_role` | admin: `name,email,birthdate,system_role` |

Exemplo de criação de turma: `{ "name":"3A", "course":"Medio", "semester":2, "year":2026, "starts_on":"2026-02-01", "ends_on":"2026-12-15" }`.

## Recados

Representante com pivot `representative` pode criar, editar e excluir recados da própria turma, independentemente do cargo global. Professor global com pivot `teacher` e admin mantêm acesso. Aluno comum e representante de outra turma recebem `403` nas escritas.

`POST /announcements` exige `title,content,class_id` e retorna `201 {data:{id,class_id,title,content,created_by}}`. `PATCH /announcements/{id}` aceita `title,content` e retorna `200` no mesmo formato; `DELETE` retorna `204`. Exemplo de POST: `{"title":"Entrega amanhã","content":"Enviar a atividade até as 18h.","class_id":"22222222-2222-4222-8222-222222222222"}`. A autoria vem do usuário autenticado; `class_id` não é alterável pelo PATCH.

## Membros

`GET/POST /school-classes/{school_class}/members`, `PATCH/DELETE /school-classes/{school_class}/members/{user_id}`; POST exige `user_id,role` (`student`, `representative` ou `teacher`), PATCH exige somente `role`. Organizações usam `GET/POST /organizations/{organization}/members` e `PATCH/DELETE /organizations/{organization}/members/{user_id}`, com `role` igual a `member`, `representative` ou `teacher`. GET retorna coleção paginada com 200; POST retorna membro com 201; PATCH retorna membro com 200; DELETE retorna 204. IDs ausentes retornam 404 e payload inválido retorna 422. Admin global ou professor global com pivot `teacher` gerenciam membros; listagens de não gestores retornam apenas o próprio usuário, e listagens autorizadas retornam `id,name,role`; dados privados adicionais aparecem apenas para o próprio usuário ou admin.

## Desempenho e dashboard

`GET /school-classes/{school_class}/performance` exige admin ou professor global com pivot `teacher`. Retorna alunos e representantes daquela turma com `id,name,tasks_total,completions_total,pending_total,percentage`, paginado. `GET /dashboard` exige admin/coordinator e retorna agregados globais e listas resumidas.

## Regras de autorização e conflitos

Representante de turma pelo pivot `representative` gerencia tarefas independentemente do cargo global. Professor precisa cargo global `teacher` e pivot `teacher`; admin não precisa vínculo. Representante local pode criar/editar/excluir posts da organização, mas não gerencia membros. Conclusão exige vínculo do Bearer com a turma, exceto admin; coordinator não cria conclusões. Duplicidade retorna `409`. Exclusão de tarefa com conclusões e exclusão de conta com FKs retornam `409` sem deixar a transação inutilizável. A gestão de eventos globais é reservada ao admin; professor só usa turma com pivot `teacher`.
## Convenção CRUD

| Método | URL | Sucesso | Envelope |
|---|---|---|---|
| GET | `/recurso` | `200` | `data[]`, `links`, `meta` |
| GET | `/recurso/{id}` | `200` | `data` |
| POST | `/recurso` | `201` | `data` |
| PUT/PATCH | `/recurso/{id}` | `200` | `data` |
| DELETE | `/recurso/{id}` | `204` | vazio |

## Permissões por recurso

| Recurso | Listar/detalhar | Criar | Alterar | Excluir |
|---|---|---|---|---|
| Turmas | usuário vinculado; admin/coordinator globais | teacher global ou admin | teacher global + pivot teacher ou admin | teacher global + pivot teacher ou admin |
| Tarefas | membro da turma; admin/coordinator | pivot representative; teacher global + pivot teacher; admin | mesmos vínculos | mesmos vínculos |
| Conclusões | próprio; teacher global com pivot teacher na turma; admin | student/representative/teacher vinculado à turma ou admin | próprio, exceto coordinator; admin | próprio, exceto coordinator; admin |
| Avisos | membro da turma; admin/coordinator | pivot representative na turma; teacher global com pivot teacher; admin | mesmos vínculos | mesmos vínculos |
| Eventos | membro da turma; admin/coordinator; eventos globais para qualquer autenticado | teacher global com pivot teacher na turma; admin; globais somente admin | teacher global com pivot teacher nas turmas de origem e destino; admin | teacher global com pivot teacher na turma; admin |
| Organizações | membro; admin/coordinator | teacher global ou admin | teacher global com pivot teacher ou admin | teacher global com pivot teacher ou admin |
| Posts | membro; admin/coordinator | representative local, teacher global com pivot teacher ou admin | mesmos vínculos | mesmos vínculos |
| Usuários | próprio; teacher vê alunos vinculados (id/name); admin | admin | admin | admin; conta própria via auth |

## Campos, relações e dashboard

Resources retornam: `User` id/name e, quando permitido, email/birthdate/system_role; `SchoolClass` id/name/course/semester/year/starts_on/ends_on; `Task` id/title/description/priority/due_date/class_id/created_by; `TaskCompletion` id/task_id/user_id/completed_at; `Announcement` id/class_id/title/content/created_by; `Organization` id/name/description/type; `Post` id/organization_id/title/description/created_by; `CalendarEvent` id/class_id/title/description/starts_at/ends_at/created_by.

As relações usam UUID e FKs: turma possui membros, tarefas, avisos e eventos; tarefa possui conclusões; organização possui membros e posts. Exclusões restritas por conclusões ou vínculos retornam `409` e preservam a transação; tokens pertencem ao usuário e são removidos atomicamente com a conta.

O dashboard retorna `summary` com users, classes, class_memberships, organizations, organization_memberships, tasks, task_completions, announcements, calendar_events e posts; também retorna classes com members_count/tasks_count, upcoming_events e recent_announcements.
As permissões de escrita não dependem da autoria do registro. `student`, `representative` e `coordinator` consultam apenas a própria conta e conclusões. Professor global com vínculo `teacher` consulta contas de membros das turmas em que leciona, sem email, nascimento ou cargo global de terceiros. O admin consulta todos. O cargo global de representante sozinho não concede gestão de tarefas ou posts: o vínculo local deve ser `representative`.

UUID malformado na URL retorna 404; UUID inválido ou referência inexistente no corpo retorna 422. As rotas públicas de registro e login permitem 10 requisições por minuto; recuperação e redefinição permitem 5. Senhas exigem no mínimo 8 caracteres e confirmação. Datas de nascimento e curso usam datas; prazos e eventos usam data/hora. `priority` aceita `low`, `medium` e `high`.

Turmas possuem vínculos, tarefas e avisos com exclusão em cascata; organizações possuem vínculos e posts com exclusão em cascata. Uma conclusão impede a exclusão da tarefa por FK restritiva. A autoria de tarefas, avisos e posts impede a exclusão do usuário por FK restritiva. A exclusão de conta remove tokens e usuário na mesma transação; conflito restaura ambos.

Exemplo de resposta de tarefa:

```json
{"data":{"id":"11111111-1111-4111-8111-111111111111","title":"Exercícios","description":null,"priority":"medium","due_date":"2026-12-01T12:00:00.000000Z","class_id":"22222222-2222-4222-8222-222222222222","created_by":"33333333-3333-4333-8333-333333333333"}}
```

Exemplo de erro de validação:

```json
{"message":"The title field is required.","errors":{"title":["The title field is required."]}}
```
