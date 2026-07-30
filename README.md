# 🕰️ Quiz Vintg — Dashboard de Leads

Quiz interativo para encontrar o relógio ideal, com captação de leads e dashboard administrativo.

## 🚀 Tecnologias

- **Front-end:** HTML + CSS + JavaScript (quiz interativo)
- **Back-end:** PHP 7.4+ (PDO)
- **Banco:** SQLite (zero configuração)

## 📁 Estrutura

```
projeto/
├── quiz.html               # Página do quiz
├── schema.sql              # Schema de referência (SQLite)
├── quiz_vintg.sqlite       # Banco de dados (criado automaticamente)
├── README.md
└── backend/
    ├── config.php           # Conexão com SQLite
    ├── submit.php           # Endpoint para salvar leads (POST)
    ├── dashboard.php        # Dashboard administrativo
    ├── lead_detail.php      # Modal com detalhes do lead
    └── export.php           # Exportar leads para CSV
```

## ▶️ Como rodar localmente

### Pré-requisitos

- [PHP](https://www.php.net/downloads) 7.4 ou superior (com as extensões `pdo_sqlite`)

Verifique se está tudo certo:

```bash
php -v
php -m | grep -E "pdo_sqlite|sqlite3"
```

### 1. Clone ou copie o projeto

```bash
cd /caminho/para/sua/pasta
git clone <url-do-repositorio> quiz-vintg
cd quiz-vintg
```

### 2. Inicie o servidor PHP

```bash
php -S localhost:8000
```

### 3. Acesse no navegador

| Página | URL |
|---|---|
| **Quiz** | [http://localhost:8000/quiz.html](http://localhost:8000/quiz.html) |
| **Dashboard** | [http://localhost:8000/backend/dashboard.php](http://localhost:8000/backend/dashboard.php) |

> ⚡ O banco SQLite (`quiz_vintg.sqlite`) é criado automaticamente na primeira execução — **não precisa rodar nenhum script SQL**.

## 🔄 Fluxo de funcionamento

1. O usuário responde o quiz no `quiz.html`
2. Na pergunta 5, ele preenche **nome** e **e-mail**
3. Ao final, o JavaScript envia os dados via `fetch()` para `backend/submit.php`
4. O PHP valida, evita duplicatas (mesmo e-mail) e salva no SQLite
5. O administrador acessa o **Dashboard** para visualizar e exportar os leads

## 📊 Dashboard

- **Cards** com total de leads e distribuição por perfil
- **Filtros** por nome, e-mail, perfil e data
- **Tabela** com paginação
- **Modal** com detalhes das respostas de cada lead
- **Exportar CSV** da lista filtrada

## ⚙️ Personalização

### Cupom

No arquivo `quiz.html`, altere o cupom nas linhas:

```javascript
document.getElementById('couponCodeResult')  // cupom na tela de resultado
document.getElementById('couponCode')         // cupom na tela final
```

### Links de produtos

Os links dos produtos estão no objeto `results` dentro do `quiz.html`, na propriedade `products[].url`.

### UTM

Os parâmetros UTM podem ser ajustados na constante:

```javascript
const UTM = "utm_source=quiz&utm_medium=site&utm_campaign=quiz_relogio_perfeito";
```

---

Feito com 🤎 para a Vintg
