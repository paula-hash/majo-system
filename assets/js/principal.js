// ============================================================
// principal.js — MAJO, LDA Sistema de Gestão de Produtos
// ============================================================

// ── SIDEBAR ──────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('aberta');
}

// ── MODAIS ───────────────────────────────────────────────────
function fecharModal() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('aberto'));
}
document.addEventListener('click', e => { if (e.target.classList.contains('modal-overlay')) fecharModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });

function confirmarExclusao(id, nome) {
    const m = document.getElementById('modal-excluir');
    if (!m) return;
    m.classList.add('aberto');
    document.getElementById('nome-produto-excluir').textContent = nome;
    document.getElementById('link-excluir').href = '?excluir=' + id;
}

// ── IMAGEM PREVIEW ───────────────────────────────────────────
function previewImagem(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const p = document.getElementById('preview-imagem');
        if (p) { p.src = e.target.result; p.style.display = 'block'; }
    };
    reader.readAsDataURL(input.files[0]);
}

// ── MARGEM DE LUCRO ──────────────────────────────────────────
function calcularMargem() {
    const c = parseFloat(document.getElementById('preco_compra')?.value) || 0;
    const v = parseFloat(document.getElementById('preco_venda')?.value) || 0;
    const m = document.getElementById('margem_lucro');
    if (m) m.value = c > 0 ? ((v - c) / c * 100).toFixed(2) : '0.00';
}

// ── NOTIFICAÇÃO TOAST ────────────────────────────────────────
function toast(msg, tipo) {
    const old = document.getElementById('toast-majo');
    if (old) old.remove();
    const d = document.createElement('div');
    d.id = 'toast-majo';
    const bg  = tipo === 'ok' ? '#d1fae5' : '#fee2e2';
    const cor = tipo === 'ok' ? '#065f46' : '#991b1b';
    const brd = tipo === 'ok' ? '#10b981' : '#ef4444';
    const ico = tipo === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle';
    d.style.cssText = `position:fixed;top:22px;right:22px;z-index:9999;padding:14px 20px;
        border-radius:12px;font-size:14px;font-weight:600;display:flex;align-items:center;
        gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.18);
        background:${bg};color:${cor};border-left:4px solid ${brd};
        animation:slideDown 0.3s ease;max-width:380px;`;
    d.innerHTML = `<i class="fas ${ico}"></i><span>${msg}</span>`;
    document.body.appendChild(d);
    setTimeout(() => { d.style.transition = 'opacity 0.4s'; d.style.opacity = '0'; setTimeout(() => d.remove(), 400); }, 3800);
}

// ── PESQUISA EM TEMPO REAL ───────────────────────────────────
function activarPesquisa(inputId, tabelaId) {
    const inp = document.getElementById(inputId);
    const tbl = document.getElementById(tabelaId);
    if (!inp || !tbl) return;

    inp.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let n = 0;
        tbl.querySelectorAll('tbody tr:not(.no-result)').forEach(tr => {
            const ok = tr.innerText.toLowerCase().includes(q);
            tr.style.display = ok ? '' : 'none';
            if (ok) n++;
        });
        // Linha "sem resultados"
        let aviso = tbl.querySelector('.no-result');
        if (n === 0 && q !== '') {
            if (!aviso) {
                aviso = document.createElement('tr');
                aviso.className = 'no-result';
                const cols = tbl.querySelector('thead tr')?.children.length || 6;
                aviso.innerHTML = `<td colspan="${cols}" style="text-align:center;padding:36px;color:#94a3b8;">
                    <i class="fas fa-search" style="font-size:28px;display:block;margin-bottom:10px;opacity:0.4;"></i>
                    Nenhum resultado para "<strong>${this.value}</strong>"</td>`;
                tbl.querySelector('tbody').appendChild(aviso);
            }
        } else if (aviso) { aviso.remove(); }
    });
}

// ── EXPORTAR EXCEL ───────────────────────────────────────────
function exportarExcel(idTabela, nomeArquivo) {
    if (typeof XLSX === 'undefined') {
        toast('A biblioteca Excel ainda está a carregar. Aguarda uns segundos e tenta de novo.', 'erro');
        return;
    }
    const tbl = document.getElementById(idTabela);
    if (!tbl) { toast('Tabela não encontrada!', 'erro'); return; }

    // Colunas a ignorar (imagem e acções)
    const colsIgnorar = new Set();
    tbl.querySelectorAll('thead th').forEach((th, i) => {
        const t = th.innerText.trim();
        if (t === 'Imagem' || t === 'Ações' || t === 'Acoes' || t === '') colsIgnorar.add(i);
    });

    const dados = [];

    // Cabeçalhos
    const cabs = [];
    tbl.querySelectorAll('thead th').forEach((th, i) => {
        if (!colsIgnorar.has(i)) cabs.push(th.innerText.trim());
    });
    dados.push(cabs);

    // Linhas visíveis
    tbl.querySelectorAll('tbody tr').forEach(tr => {
        if (tr.style.display === 'none' || tr.classList.contains('no-result')) return;
        const linha = [];
        tr.querySelectorAll('td').forEach((td, i) => {
            if (colsIgnorar.has(i)) return;
            linha.push(td.innerText.trim().replace(/\s+/g, ' '));
        });
        if (linha.some(v => v)) dados.push(linha);
    });

    // Rodapé se existir
    tbl.querySelectorAll('tfoot tr').forEach(tr => {
        const l = [];
        tr.querySelectorAll('td').forEach((td, i) => { if (!colsIgnorar.has(i)) l.push(td.innerText.trim()); });
        if (l.some(v => v)) dados.push(l);
    });

    if (dados.length <= 1) { toast('Não há dados para exportar!', 'erro'); return; }

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(dados);

    // Estilo largura colunas
    ws['!cols'] = cabs.map(() => ({ wch: 22 }));

    XLSX.utils.book_append_sheet(wb, ws, 'Produtos MAJO');
    XLSX.writeFile(wb, nomeArquivo + '.xlsx');
    toast('Ficheiro Excel exportado com sucesso!', 'ok');
}

// ── EXPORTAR PDF ─────────────────────────────────────────────
function exportarPDF(titulo) {
    if (typeof window.jspdf === 'undefined') {
        toast('A biblioteca PDF ainda está a carregar. Aguarda uns segundos e tenta de novo.', 'erro');
        return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const PW = 297, PH = 210;

    // ── Cabeçalho ──
    doc.setFillColor(26, 60, 94);
    doc.rect(0, 0, PW, 24, 'F');
    doc.setFillColor(245, 158, 11);
    doc.rect(0, 0, 5, 24, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(16);
    doc.text('MAJO, LDA', 12, 11);
    doc.setFont('helvetica', 'normal'); doc.setFontSize(9);
    doc.text(titulo, 12, 18);
    doc.setFontSize(8);
    doc.text('Gerado em: ' + new Date().toLocaleDateString('pt-PT') + '  ' + new Date().toLocaleTimeString('pt-PT'), PW - 12, 14, { align: 'right' });
    doc.setTextColor(30, 41, 59);

    // ── Recolher dados ──
    const tbl = document.getElementById('tabela-principal') || document.getElementById('tabela-relatorio');
    if (!tbl) { toast('Tabela não encontrada!', 'erro'); return; }

    const colsIgnorar = new Set();
    tbl.querySelectorAll('thead th').forEach((th, i) => {
        const t = th.innerText.trim();
        if (t === 'Imagem' || t === 'Ações' || t === 'Acoes' || t === '') colsIgnorar.add(i);
    });

    const cabs = [];
    tbl.querySelectorAll('thead th').forEach((th, i) => {
        if (!colsIgnorar.has(i)) cabs.push(th.innerText.trim());
    });

    const linhas = [];
    tbl.querySelectorAll('tbody tr').forEach(tr => {
        if (tr.style.display === 'none' || tr.classList.contains('no-result')) return;
        const l = [];
        tr.querySelectorAll('td').forEach((td, i) => {
            if (colsIgnorar.has(i)) return;
            l.push(td.innerText.trim().replace(/\s+/g, ' ').substring(0, 28));
        });
        if (l.some(v => v)) linhas.push(l);
    });

    if (linhas.length === 0) { toast('Não há dados para exportar!', 'erro'); return; }

    // ── Desenhar tabela ──
    const MG = 10, LARG = PW - MG * 2;
    const colW = Math.floor(LARG / cabs.length);
    let y = 32;

    const desenharCab = () => {
        doc.setFillColor(37, 99, 168);
        doc.rect(MG, y, LARG, 9, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5);
        cabs.forEach((c, i) => doc.text(c.substring(0, 22), MG + i * colW + 2, y + 6.2));
        y += 9;
        doc.setFont('helvetica', 'normal'); doc.setFontSize(7);
        doc.setTextColor(30, 41, 59);
    };
    desenharCab();

    linhas.forEach((lin, idx) => {
        if (y > PH - 18) {
            // Rodapé página actual
            doc.setFillColor(26, 60, 94);
            doc.rect(0, PH - 10, PW, 10, 'F');
            doc.setTextColor(255, 255, 255); doc.setFontSize(7);
            doc.text('MAJO, LDA — Sistema de Gestão de Produtos', MG, PH - 4);
            doc.text('Página ' + doc.internal.getCurrentPageInfo().pageNumber, PW - MG, PH - 4, { align: 'right' });
            doc.addPage(); y = 14; desenharCab();
        }
        if (idx % 2 === 0) { doc.setFillColor(248, 250, 252); doc.rect(MG, y, LARG, 7.5, 'F'); }
        doc.setDrawColor(226, 232, 240); doc.setLineWidth(0.2);
        doc.rect(MG, y, LARG, 7.5);
        lin.forEach((cel, i) => doc.text(String(cel), MG + i * colW + 2, y + 5.2));
        y += 7.5;
    });

    // ── Rodapé em todas as páginas ──
    const total = doc.internal.getNumberOfPages();
    for (let p = 1; p <= total; p++) {
        doc.setPage(p);
        doc.setFillColor(26, 60, 94);
        doc.rect(0, PH - 10, PW, 10, 'F');
        doc.setTextColor(255, 255, 255); doc.setFontSize(7);
        doc.text('MAJO, LDA — Sistema de Gestão de Produtos', MG, PH - 4);
        doc.text('Página ' + p + ' de ' + total, PW - MG, PH - 4, { align: 'right' });
    }

    doc.save(titulo.replace(/[^a-zA-Z0-9_]/g, '_') + '.pdf');
    toast('Ficheiro PDF exportado com sucesso!', 'ok');
}

// ── ALERTAS AUTO-FECHAR ──────────────────────────────────────
setTimeout(() => {
    const a = document.getElementById('alertaMsg');
    if (a) { a.style.transition = 'opacity 0.5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }
}, 5000);

// ── INICIALIZAR ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Margem de lucro
    const cc = document.getElementById('preco_compra');
    const cv = document.getElementById('preco_venda');
    if (cc) cc.addEventListener('input', calcularMargem);
    if (cv) cv.addEventListener('input', calcularMargem);

    // Pesquisa nas tabelas
    activarPesquisa('pesquisa-rapida', 'tabela-principal');
    activarPesquisa('pesquisa-rapida', 'tabela-relatorio');
});
