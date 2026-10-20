# API

Todas as rotas protegidas usam `Authorization: Bearer <token>`. Respostas de entidades usam `data`; coleções paginadas usam `data`, `links` e `meta`, com `per_page` padrão 20 e máximo 100.

## Autenticação

- `POST /api/auth/register`: `name`, `birthdate`, `email`, `password`, `password_confirmation`; retorna `201` com `data.user` e `data.token`.
- `POST /api/auth/login`: `email`, `password`; retorna `200` com `data.user` e `data.token`; credencial inválida retorna `401`.
- `POST /api/auth/logout`: revoga o token atual e retorna `204`.
- `GET /api/auth/me`: retorna o usuário autenticado.
- `PUT /api/auth/me`: atualiza perfil; alteração de senha revoga tokens.
- `DELETE /api/auth/me`: remove conta atomicamente e retorna `204`, ou `409` quando há vínculos.
- `POST /api/auth/forgot-password`: resposta genérica, sem revelar existência do email.
- `POST /api/auth/reset-password`: `token`, `email`, `password`, `password_confirmation`; token é de uso único e revoga tokens.

## Recursos

CRUD `apiResource`: `/school-classes`, `/tasks`, `/task-completions`, `/announcements`, `/organizations`, `/posts`, `/calendar-events`, `/users`. IDs usam UUID. Validação retorna `422` com `message` e `errors`; ausência `404`; permissão `403`; conflito `409`.

Turmas exigem `starts_on` e `ends_on` na criação, com fim maior ou igual ao início. `GET /api/school-classes/{school_class}/performance` lista alunos e representantes da turma com contagem de tarefas, conclusões, pendências e percentual; somente admin ou professor global com pivot `teacher` acessa.

Conclusões usam sempre o usuário Bearer, exigem vínculo com a turma da tarefa e duplicidade retorna `409`. Exclusão de tarefa com conclusões retorna `409`.

## Membros e permissões

Membros de turma e organização usam `GET`, `POST`, `PATCH /{user_id}` e `DELETE`. Gestão exige admin ou professor global com pivot `teacher`. Papéis de organização: `member`, `representative`, `teacher`. Representante local pode gerenciar posts da organização; não gerencia membros.

Representante de turma pelo pivot `representative` gerencia tarefas independentemente do cargo global. Professor precisa cargo global `teacher` e pivot `teacher`. Admin global não precisa vínculo. Dados de terceiros expõem somente `id` e `name` quando aplicável; email e nascimento ficam restritos ao próprio usuário e admin.

Rotas de autenticação aplicam throttle. Eventos globais podem ser criados apenas por admin; professor precisa turma vinculada com pivot `teacher`.