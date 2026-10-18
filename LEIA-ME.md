# 📦 MAJO, LDA — Sistema de Gestão de Produtos
### Guia Completo de Instalação e Uso

---

## 🗂️ ESTRUTURA DE PASTAS

```
majo_sistema/
│
├── index.php                  ← Entrada (redireciona para login)
├── login.php                  ← Página de login
├── logout.php                 ← Terminar sessão
├── banco_de_dados.sql         ← Criar o banco de dados (executar uma vez)
│
├── includes/
│   ├── conexao.php            ← Configuração da ligação ao MySQL
│   ├── sessao.php             ← Funções de sessão e segurança
│   ├── header.php             ← Cabeçalho e menu (incluído em todas as páginas)
│   └── footer.php             ← Rodapé
│
├── assets/
│   ├── css/
│   │   └── estilo.css         ← Todo o estilo visual do sistema
│   └── js/
│       └── principal.js       ← Comportamentos interativos
│
├── pages/
│   ├── dashboard.php          ← Página inicial com gráficos
│   ├── relatorios.php         ← Relatórios + exportação PDF/Excel
│   ├── historico.php          ← Histórico de ações
│   ├── produtos/
│   │   ├── listar.php         ← Lista de produtos
│   │   ├── criar.php          ← Cadastrar novo produto
│   │   ├── editar.php         ← Editar produto
│   │   └── ver.php            ← Ver detalhes + QR Code
│   ├── categorias/
│   │   └── listar.php         ← Gerir categorias
│   └── utilizadores/
│       └── listar.php         ← Gerir utilizadores (só admin)
│
└── uploads/
    └── produtos/              ← Imagens dos produtos (criada automaticamente)
```

---

## 🚀 COMO INSTALAR (passo a passo)

### PASSO 1 — Instalar o XAMPP
1. Vai ao site: https://www.apachefriends.org
2. Baixa e instala o XAMPP
3. Abre o XAMPP Control Panel
4. Clica **Start** no Apache e no **MySQL**

---

### PASSO 2 — Copiar os ficheiros
1. Vai à pasta: `C:\xampp\htdocs\` (Windows) ou `/opt/lampp/htdocs/` (Linux)
2. Cria uma pasta chamada `majo_sistema`
3. Copia TODOS os ficheiros do projeto para dentro dessa pasta

---

### PASSO 3 — Criar o banco de dados
1. Abre o browser e vai a: `http://localhost/phpmyadmin`
2. Clica em **"SQL"** no menu do topo
3. Abre o ficheiro `banco_de_dados.sql` com o Bloco de Notas
4. Copia todo o conteúdo e cola no phpMyAdmin
5. Clica em **"Executar"**
6. Deves ver a mensagem: "Consulta executada com êxito"

---

### PASSO 4 — Aceder ao sistema
1. Abre o browser
2. Vai a: `http://localhost/majo_sistema`
3. Usa as credenciais padrão:
   - **Email:** admin@majo.com
   - **Senha:** password

---

## 🔧 CONFIGURAÇÕES

### Mudar as credenciais do banco de dados
Abre o ficheiro `includes/conexao.php` e edita:
```php
define('DB_HOST', 'localhost');    // normalmente não muda
define('DB_USER', 'root');         // utilizador do MySQL
define('DB_PASS', '');             // senha (vazia no XAMPP)
define('DB_NAME', 'majo_sistema'); // nome do banco
```

---

## ✨ FUNCIONALIDADES

| Funcionalidade          | Descrição                                      |
|-------------------------|------------------------------------------------|
| ✅ Login/Logout          | Autenticação segura com senha encriptada       |
| ✅ Dashboard             | Gráficos e estatísticas em tempo real          |
| ✅ Cadastro de Produtos  | Criar, editar, visualizar e excluir produtos   |
| ✅ Upload de Imagem      | Foto do produto com preview antes de enviar    |
| ✅ QR Code               | Gerado automaticamente para cada produto       |
| ✅ Categorias            | Criar e gerir categorias                       |
| ✅ Pesquisa e Filtros    | Por nome, código, categoria e status           |
| ✅ Controlo de Stock     | Alerta quando stock está abaixo do mínimo      |
| ✅ Relatórios            | Com exportação para Excel e PDF                |
| ✅ Histórico             | Registo de todas as ações (quem fez o quê)     |
| ✅ Utilizadores          | Criar e gerir utilizadores (admin/operador)    |
| ✅ Cálculo de Margem     | Calculada automaticamente ao inserir preços    |

---

## 🎓 EXPLICAÇÃO DAS TECNOLOGIAS

### PHP
- Linguagem de programação que corre no servidor
- Gere os dados, faz as queries ao banco de dados
- Verifica login, processa formulários

### MySQL
- Banco de dados onde ficam guardados todos os dados
- Tabelas: utilizadores, produtos, categorias, historico

### HTML + CSS
- HTML: estrutura das páginas (formulários, tabelas, menus)
- CSS: visual (cores, tamanhos, layout)

### Como se ligam:
```
Browser (HTML/CSS) ←→ PHP ←→ MySQL
     (o utilizador vê)   (processa)  (guarda os dados)
```

---

## ⚠️ PROBLEMAS COMUNS

**"Erro de Conexão"**
→ Verifica se o MySQL está iniciado no XAMPP

**"Página não encontrada"**
→ Confirma que a pasta se chama `majo_sistema` em htdocs

**"Tabela não existe"**
→ Executa novamente o `banco_de_dados.sql` no phpMyAdmin

---

*Desenvolvido para o TCC — MAJO, LDA © 2025*
