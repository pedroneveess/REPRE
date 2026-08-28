const API_HOST = window.location.hostname || '127.0.0.1';
const API_URL = `http://${API_HOST}:8000/api`;

async function createUser(
    name,
    birthdate,
    email,
    password,
    password_confirmation
) {

    const response = await fetch(`${API_URL}/signUp`, {
        method: 'POST',

        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },

        body: JSON.stringify({
            name,
            birthdate,
            email,
            password,
            password_confirmation
        })
    });

    const data = await response.json();

    console.log('Resposta da API:', data);

    if (!response.ok) {
        throw data;
    }

    return data;
}


document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('cadastroForm');
    const mensagem = document.getElementById('mensagem');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (e) => {

        e.preventDefault();

        mensagem.innerText = 'Criando conta...';

        const name = document.getElementById('name').value;
        const birthdate = document.getElementById('birthdate').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const password_confirmation =
            document.getElementById('password_confirmation').value;

        try {

            const data = await createUser(
                name,
                birthdate,
                email,
                password,
                password_confirmation
            );

            console.log('Usuário criado:', data);

            localStorage.setItem('token', data.token);
            localStorage.setItem(
                'user',
                JSON.stringify(data.user)
            );

            mensagem.style.color = 'green';
            mensagem.innerText = 'Conta criada com sucesso!';

            setTimeout(() => {
                window.location.href = 'home.html';
            }, 800);

        } catch (erro) {

            console.error('Erro no cadastro:', erro);

            mensagem.style.color = 'red';

            if (erro.errors) {

                const primeiroErro =
                    Object.values(erro.errors)[0][0];

                mensagem.innerText = primeiroErro;

            } else {

                mensagem.innerText =
                    erro.message || 'Erro ao criar conta.';
            }
        }
    });
});