Projeto de TCC


Para iniciar precisa do docker desktop inicializado e posteriormente digite no teminal: 

//Inicia o container docker para testar o aplicação:

docker compose up -d

//Destroi o container:

docker compose down


No PC da Etec pode dar erro, oque resolveu aqui é entrar no powershell como Administrador e colocar: 
wsl --shutdown
ipconfig /flushdns
netsh winsock reset


Portas para testar: 

http://localhost:8080  - frontend/site

http://localhost:8081  - phpmyadmin/banco de dados

http://localhost:8000  - backend/api, rotas e mais