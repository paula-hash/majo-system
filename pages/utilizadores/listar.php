<?php
require_once '../../includes/sessao.php';
require_once '../../includes/conexao.php';
verificarAdmin();

// ── EXCLUIR ─────────────────────────────────────────────────
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    if ($id == $_SESSION['utilizador_id']) {
        setMensagem('erro', 'Não podes excluir a tua própria conta!');
    } else {
        $u = $conn->query("SELECT nome FROM utilizadores WHERE id=$id")->fetch_assoc();
        if ($u) {
            $conn->query("DELETE FROM utilizadores WHERE id=$id");
            registarHistorico($conn, 'EXCLUIR', 'utilizadores', $id, "Utilizador '{$u['nome']}' excluído");
            setMensagem('sucesso', "Utilizador '{$u['nome']}' excluído.");
        }
    }
    header("Location: /majo_system/pages/utilizadores/listar.php");
    exit();
}

// ── CRIAR / EDITAR ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome    = trim($_POST['nome']   ?? '');
    $email   = trim($_POST['email']  ?? '');
    $perfil  = $_POST['perfil']      ?? 'operador';
    $senha   = trim($_POST['senha']  ?? '');
    $id_edit = (int)($_POST['id_edit'] ?? 0);
    $erros   = [];

    if (empty($nome))  $erros[] = "Nome obrigatório.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Email inválido.";
    if (!in_array($perfil, ['admin','gerente','operador'])) $perfil = 'operador';

    $check = $conn->prepare("SELECT id FROM utilizadores WHERE email=? AND id!=?");
    $check->bind_param("si", $email, $id_edit);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $erros[] = "Email já em uso.";

    if (empty($erros)) {
        if ($id_edit) {
            if (!empty($senha)) {
                if (strlen($senha) < 6) { $erros[] = "Senha deve ter pelo menos 6 caracteres."; }
                else {
                    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost'=>12]);
                    $stmt = $conn->prepare("UPDATE utilizadores SET nome=?,email=?,perfil=?,senha=? WHERE id=?");
                    $stmt->bind_param("ssssi",$nome,$email,$perfil,$hash,$id_edit);
                }
            } else {
                $stmt = $conn->prepare("UPDATE utilizadores SET nome=?,email=?,perfil=? WHERE id=?");
                $stmt->bind_param("sssi",$nome,$email,$perfil,$id_edit);
            }
            if (empty($erros)) {
                $stmt->execute();
                registarHistorico($conn,'EDITAR','utilizadores',$id_edit,"Utilizador '$nome' editado");
                setMensagem('sucesso',"Utilizador '$nome' atualizado!");
            }
        } else {
            if (empty($senha) || strlen($senha) < 6) {
                $erros[] = "Senha obrigatória (mínimo 6 caracteres).";
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost'=>12]);
                $stmt = $conn->prepare("INSERT INTO utilizadores (nome,email,senha,perfil) VALUES(?,?,?,?)");
                $stmt->bind_param("ssss",$nome,$email,$hash,$perfil);
                $stmt->execute();
                $nid = $conn->insert_id;
                registarHistorico($conn,'CRIAR','utilizadores',$nid,"Utilizador '$nome' criado");
                setMensagem('sucesso',"Utilizador '$nome' criado!");
            }
        }
        if (!empty($erros)) setMensagem('erro', implode(' ', $erros));
    } else {
        setMensagem('erro', implode(' ', $erros));
    }
    header("Location: /majo_system/pages/utilizadores/listar.php");
    exit();
}

$utilizadores = $conn->query("SELECT * FROM utilizadores ORDER BY nome");
require_once '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-titulo"><i class="fas fa-users"></i> Utilizadores</div>
        <div class="page-subtitulo">Gerir os utilizadores do sistema</div>
    </div>
    <button onclick="abrirModal()" class="btn btn-acento">
        <i class="fas fa-plus"></i> Novo Utilizador
    </button>
</div>

<div style="margin-bottom:14px;">
    <input type="text" id="pesquisa-rapida" class="input-pesquisa" style="max-width:320px;" placeholder="Pesquisar utilizador...">
</div>

<div class="card">
    <div class="tabela-container">
        <table id="tabela-principal">
            <thead>
                <tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Criado em</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php while($u = $utilizadores->fetch_assoc()):
                    $badge = ['admin'=>'badge-aviso','gerente'=>'badge-aviso','operador'=>'badge-ativo'];
                    $b = $badge[$u['perfil']] ?? 'badge-ativo';
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="user-avatar" style="width:34px;height:34px;font-size:13px;">
                                <?= strtoupper(substr($u['nome'],0,1)) ?>
                            </div>
                            <strong><?= htmlspecialchars($u['nome']) ?></strong>
                            <?php if($u['id']==$_SESSION['utilizador_id']): ?>
                                <span class="badge badge-ativo" style="font-size:10px;">Tu</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge <?= $b ?>"><?= ucfirst($u['perfil']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <!-- VER -->
                            <button onclick='verUtilizador(<?= json_encode($u) ?>)'
                                    class="btn btn-outline btn-sm btn-icone" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                            <!-- EDITAR (não para o próprio admin apagar-se) -->
                            <button onclick='abrirModal(<?= json_encode($u) ?>)'
                                    class="btn btn-primario btn-sm btn-icone" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- EXCLUIR (não pode excluir a si mesmo) -->
                            <?php if($u['id'] != $_SESSION['utilizador_id']): ?>
                            <button onclick="confirmarExcluirUser(<?= $u['id'] ?>, '<?= addslashes($u['nome']) ?>')"
                                    class="btn btn-perigo btn-sm btn-icone" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL VER UTILIZADOR -->
<div class="modal-overlay" id="modal-ver-user">
    <div class="modal" style="max-width:420px;">
        <div class="modal-titulo"><i class="fas fa-user"></i> Detalhes do Utilizador</div>
        <div id="ver-user-conteudo"></div>
        <div class="modal-acoes">
            <button onclick="fecharModal()" class="btn btn-outline"><i class="fas fa-times"></i> Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL CRIAR/EDITAR -->
<div class="modal-overlay" id="modal-utilizador">
    <div class="modal" style="max-width:440px;">
        <div class="modal-titulo" id="modal-titulo"><i class="fas fa-user-plus"></i> Novo Utilizador</div>
        <form method="POST">
            <input type="hidden" name="id_edit" id="campo-id" value="0">
            <div class="form-grupo" style="margin-bottom:12px;">
                <label>Nome Completo *</label>
                <input type="text" name="nome" id="campo-nome" class="form-control" required>
            </div>
            <div class="form-grupo" style="margin-bottom:12px;">
                <label>Email *</label>
                <input type="email" name="email" id="campo-email" class="form-control" required>
            </div>
            <div class="form-grupo" style="margin-bottom:12px;">
                <label>Perfil</label>
                <select name="perfil" id="campo-perfil" class="form-control">
                    <option value="operador">Operador</option>
                    <option value="gerente">Gerente</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-grupo" style="margin-bottom:20px;">
                <label>Senha <span id="senha-nota" style="color:var(--texto-claro);font-weight:normal;">(obrigatória)</span></label>
                <div style="position:relative;">
                    <input type="password" name="senha" id="campo-senha" class="form-control" placeholder="Mínimo 6 caracteres" style="padding-right:44px;">
                    <button type="button" onclick="toggleSenhaModal()" title="Mostrar/ocultar senha"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--texto-claro);">
                        <i class="fas fa-eye" id="icon-senha-modal"></i>
                    </button>
                </div>
            </div>
            <div class="modal-acoes">
                <button type="button" onclick="fecharModal()" class="btn btn-outline"><i class="fas fa-times"></i> Cancelar</button>
                <button type="submit" class="btn btn-acento"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR EXCLUSÃO -->
<div class="modal-overlay" id="modal-excluir-user">
    <div class="modal" style="max-width:420px;">
        <div class="modal-titulo" style="color:var(--perigo);"><i class="fas fa-trash"></i> Confirmar Exclusão</div>
        <p>Tens a certeza que queres excluir o utilizador <strong id="nome-user-excluir"></strong>?</p>
        <p style="color:var(--texto-claro);font-size:13px;margin-top:8px;">Esta acção não pode ser desfeita.</p>
        <div class="modal-acoes">
            <button onclick="fecharModal()" class="btn btn-outline"><i class="fas fa-times"></i> Cancelar</button>
            <a id="link-excluir-user" href="#" class="btn btn-perigo"><i class="fas fa-trash"></i> Excluir</a>
        </div>
    </div>
</div>

<script>
// Abrir modal criar/editar
function abrirModal(u) {
    fecharModal();
    document.getElementById('modal-utilizador').classList.add('aberto');
    if (u) {
        document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-edit"></i> Editar Utilizador';
        document.getElementById('campo-id').value    = u.id;
        document.getElementById('campo-nome').value  = u.nome;
        document.getElementById('campo-email').value = u.email;
        document.getElementById('campo-perfil').value= u.perfil;
        document.getElementById('campo-senha').placeholder = 'Deixa vazio para não alterar';
        document.getElementById('senha-nota').textContent  = '(deixa vazio para não alterar)';
    } else {
        document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-user-plus"></i> Novo Utilizador';
        document.getElementById('campo-id').value    = '0';
        document.getElementById('campo-nome').value  = '';
        document.getElementById('campo-email').value = '';
        document.getElementById('campo-perfil').value= 'operador';
        document.getElementById('campo-senha').value = '';
        document.getElementById('campo-senha').placeholder = 'Mínimo 6 caracteres';
        document.getElementById('senha-nota').textContent  = '(obrigatória)';
    }
}

// Ver detalhes
function verUtilizador(u) {
    fecharModal();
    const perfis = {admin:'Administrador',gerente:'Gerente',operador:'Operador'};
    document.getElementById('ver-user-conteudo').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px;">
            <div style="background:var(--fundo);padding:14px;border-radius:10px;">
                <div style="font-size:11px;color:var(--texto-claro);text-transform:uppercase;margin-bottom:4px;">Nome</div>
                <strong>${u.nome}</strong>
            </div>
            <div style="background:var(--fundo);padding:14px;border-radius:10px;">
                <div style="font-size:11px;color:var(--texto-claro);text-transform:uppercase;margin-bottom:4px;">Perfil</div>
                <strong>${perfis[u.perfil] || u.perfil}</strong>
            </div>
            <div style="background:var(--fundo);padding:14px;border-radius:10px;grid-column:1/-1;">
                <div style="font-size:11px;color:var(--texto-claro);text-transform:uppercase;margin-bottom:4px;">Email</div>
                <strong>${u.email}</strong>
            </div>
            <div style="background:var(--fundo);padding:14px;border-radius:10px;grid-column:1/-1;">
                <div style="font-size:11px;color:var(--texto-claro);text-transform:uppercase;margin-bottom:4px;">Criado em</div>
                <strong>${u.criado_em}</strong>
            </div>
        </div>`;
    document.getElementById('modal-ver-user').classList.add('aberto');
}

// Confirmar exclusão
function confirmarExcluirUser(id, nome) {
    fecharModal();
    document.getElementById('nome-user-excluir').textContent = nome;
    document.getElementById('link-excluir-user').href = '?excluir=' + id;
    document.getElementById('modal-excluir-user').classList.add('aberto');
}

// Toggle senha no modal
function toggleSenhaModal() {
    const inp  = document.getElementById('campo-senha');
    const icon = document.getElementById('icon-senha-modal');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
