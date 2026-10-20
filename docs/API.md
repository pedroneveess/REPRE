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

Todos os IDs são UUID. Os endpoints abaixo aceitam `GET` lista, `GET /{id}`, `POST`, `PUT/PATCH /{id}` e `DELETE /{id}`. POST/PATCH usam os campos indicados; `created_by`, `user_id` e papéis são definidos no servidor.

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

## Membros

`GET/POST /school-classes/{school_class}/members`, `PATCH/DELETE /school-classes/{school_class}/members/{user_id}`; POST exige `user_id,role` (`student|representative|teacher`), PATCH exige somente `role`. As rotas equivalentes de organização usam `member|representative|teacher`. Admin global ou professor global com pivot `teacher` gerenciam membros; listagens de não gestores retornam apenas o próprio usuário, e listagens autorizadas retornam `id,name,membership role`.

## Desempenho e dashboard

`GET /school-classes/{school_class}/performance` exige admin ou professor global com pivot `teacher`. Retorna alunos e representantes daquela turma com `id,name,tasks_total,completions_total,pending_total,percentage`, paginado. `GET /dashboard` exige admin/coordinator e retorna agregados globais e listas resumidas.

## Regras de autorização e conflitos

Representante de turma pelo pivot `representative` gerencia tarefas independentemente do cargo global. Professor precisa cargo global `teacher` e pivot `teacher`; admin não precisa vínculo. Representante local pode criar/editar/excluir posts da organização, mas não gerencia membros. Conclusão exige vínculo do Bearer com a turma e duplicidade retorna `409`. Exclusão de tarefa com conclusões e exclusão de conta com FKs retornam `409` sem deixar a transação inutilizável. Eventos globais são reservados ao admin; professor só usa turma com pivot `teacher`.