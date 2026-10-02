/* RESUMO WRAPPER */
document.addEventListener('DOMContentLoaded', () => {

    const resumoScroll = document.getElementById('resumoScroll');

    if (resumoScroll) {

        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;

        resumoScroll.addEventListener('mousedown', (e) => {
            isDragging = true;
            resumoScroll.classList.add('grabbing');

            startX = e.pageX;
            scrollLeft = resumoScroll.scrollLeft;
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            e.preventDefault();

            const walk = e.pageX - startX;
            resumoScroll.scrollLeft = scrollLeft - walk;
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            resumoScroll.classList.remove('grabbing');
        });
    }


    /* ATIVIDADES WRAPPER */
    const atividades = [
        {
            materia: "matematica",
            titulo: "Lista de Exercícios",
            data: "20/06/2026",
            status: "Pendente"
        },
        {
            materia: "filosofia",
            titulo: "Questionário",
            data: "22/06/2026",
            status: "Pendente"
        },
        {
            materia: "geografia",
            titulo: "Pesquisa sobre Globalização",
            data: "25/06/2026",
            status: "Pendente"
        },
        {
            materia: "ingles",
            titulo: "Seminário de Filmes",
            data: "27/06/2026",
            status: "Pendente"
        }
    ];

    const container = document.getElementById('cards-container');
    console.log('Container:', container);

    if (!container) {
        console.error('Elemento #cards-container não encontrado');
        return;
    }

    function criarCardAtividade({
        materia,
        titulo,
        data,
        status
    }) {
        return `
    <div class="card-atividades">

        <div class="card-atividades-title">
            ${materia}
        </div>

        <div class="atividade-conteudo">

            <h3 class="card-atividades-conteudo">
                ${titulo}
            </h3>

            <div class="atividade-informacoes">
                <span class="card-atividades-data">
                    <i class="fa-regular fa-calendar"></i>
                    ${data}
                </span>
            </div>

        </div>

        <div class="atividade-status">
            <span class="card-atividades-status">
                ${status}
            </span>
        </div>

    </div>
`;
    }

    console.log('Atividades:', atividades);
    container.innerHTML = atividades
        .map(criarCardAtividade)
        .join('');

    console.log('HTML gerado:', container.innerHTML);

});

    /* CALENDÁRIO */
const monthYear = document.getElementById("mes");
const daysContainer = document.getElementById("calendarDays");
const eventInfo = document.getElementById("eventoInfo");


const eventos = {
    "2026-04-24": {
        tipo: "prova",
        titulo: "Provão Paulista"
    },

    "2026-04-28": {
        tipo: "olimpiada",
        titulo: "OBMEP"
    },

    "2026-06-03": {
        tipo: "prova",
        titulo: "Prova"
    },

    "2026-06-08": {
        tipo: "prova",
        titulo: "Prova"
    },

    "2026-06-12": {
        tipo: "festa",
        titulo: "Festa Junina"
    },

    "2026-06-22": {
        tipo: "festa",
        titulo: "Festa"
    },

    "2026-07-06": {
        tipo: "ferias",
        titulo: "Início das Férias"
    }
};


let currentDate = new Date();


function renderCalendar() {

    daysContainer.innerHTML = "";

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    const months = [
        "Janeiro",
        "Fevereiro",
        "Março",
        "Abril",
        "Maio",
        "Junho",
        "Julho",
        "Agosto",
        "Setembro",
        "Outubro",
        "Novembro",
        "Dezembro"
    ];
    monthYear.textContent = `${months[month]} ${year}`;


    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement("div");
        daysContainer.appendChild(emptyDay);
    }


    for (let day = 1; day <= totalDays; day++) {
        const fullDate =
            `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        const div = document.createElement("div");

        div.classList.add("day");
        div.textContent = day;

        if (eventos[fullDate]) {
            div.classList.add(eventos[fullDate].tipo);
        }

        const hoje = new Date();

        if (
            day === hoje.getDate() &&
            month === hoje.getMonth() &&
            year === hoje.getFullYear()
        ) {
            div.classList.add("today");
        }

        div.addEventListener("click", () => {

            if (eventos[fullDate]) {
                eventInfo.innerHTML = `
                    <strong>${eventos[fullDate].titulo}</strong>
                    <br>
                    ${fullDate}
                `;
            } else {
                eventInfo.innerHTML = `
                    Nenhum evento nesta data
                `;
            }
        });

        daysContainer.appendChild(div);
    }
}

renderCalendar();

document.getElementById("next").addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
});


document.getElementById("prev").addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
});


const proximos = [
    {
        materia: "Prova",
        titulo: "OBMEP",
        data: "03/06/2026"
    },

    {
        materia: "Festa",
        titulo: "Festa Junina",
        data: "12/06/2026",
        status: "Concluída"
    },

    {
        materia: "Prova",
        titulo: "Prova",
        data: "08/06/2026"
    },

    {
        materia: "Festa",
        titulo: "Festa",
        data: "22/06/2026",
        status: "Concluída"
    }
];


const proximosContainer =
    document.getElementById("proximos-container");

if (!proximosContainer) {
    console.error(
        "Elemento #proximos-container não encontrado"
    );
} else {
    proximosContainer.innerHTML = proximos
        .map(criarCardAtividade)
        .join("");
}


function criarCardAtividade({
    materia,
    titulo,
    data,
    status
}) {
    return `
        <div class="card-proximos">

            <div class="card-proximos-title">
                ${materia} - ${titulo}
            </div>

            <div class="card-proximos-data">
                ${data}
            </div>

        </div>
    `;
}