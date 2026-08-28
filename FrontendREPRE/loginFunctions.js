const API_HOST = window.location.hostname || '127.0.0.1';
const API_URL = `http://${API_HOST}:8000/api`;

async function loginUser(email, password) {
    const response = await fetch(`${API_URL}/signIn`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    if (!response.ok) {
        throw data;
    }

    return data;
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const mensagem = document.getElementById('mensagem');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        mensagem.style.color = '';
        mensagem.innerText = 'Entrando...';

        try {
            const data = await loginUser(
                document.getElementById('email').value,
                document.getElementById('password').value
            );

            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = 'home.html';
        } catch (error) {
            mensagem.style.color = 'red';
            mensagem.innerText = error.errors?.email?.[0]
                || error.message
                || 'E-mail ou senha inválidos.';
        }
    });
});
