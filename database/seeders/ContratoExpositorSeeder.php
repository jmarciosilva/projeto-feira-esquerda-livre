<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class ContratoExpositorSeeder extends Seeder
{
    public function run(): void
    {
        $contrato = <<<'HTML'
<h2>CONTRATO DE PARTICIPAÇÃO — EXPOSITOR FEIRA ESQUERDA LIVRE</h2>

<p>Este instrumento regula os termos de participação do(a) EXPOSITOR(A) na plataforma e nos eventos presenciais da <strong>Feira Esquerda Livre</strong>, doravante denominada simplesmente <strong>FEIRA</strong>.</p>

<hr>

<h3>1. DAS PARTES</h3>

<p><strong>1.1. FEIRA:</strong> Feira Esquerda Livre, plataforma digital de marketplace e agenda de feiras populares, comprometida com a economia solidária, a produção independente e a cultura de base.</p>

<p><strong>1.2. EXPOSITOR(A):</strong> Pessoa física ou jurídica que solicita e recebe aprovação para comercializar produtos ou serviços por meio desta plataforma e/ou nos eventos físicos organizados ou credenciados pela FEIRA.</p>

<hr>

<h3>2. DO OBJETO</h3>

<p><strong>2.1.</strong> O presente contrato tem por objeto a concessão de espaço digital (loja virtual) e/ou espaço físico (banca/estande) ao(à) EXPOSITOR(A) para exposição e venda de itens enquadrados em um ou mais dos seguintes eixos:</p>

<ul>
  <li>🛍️ <strong>Produtos</strong> — artesanato, livros, roupas, alimentos, cosméticos naturais e similares;</li>
  <li>🎯 <strong>Serviços</strong> — design, comunicação, aulas, consultorias e atividades afins;</li>
  <li>🌿 <strong>Cuidados &amp; Bem Viver</strong> — massagens, terapias, práticas integrativas e similares.</li>
</ul>

<p><strong>2.2.</strong> A participação somente abrange os eixos declarados e aprovados no momento do cadastro. A inclusão de novo eixo requer atualização cadastral e nova aprovação.</p>

<hr>

<h3>3. DAS OBRIGAÇÕES DO(A) EXPOSITOR(A)</h3>

<p><strong>3.1.</strong> Manter atualizados os dados cadastrais, incluindo WhatsApp, e-mail, chave PIX e endereço da loja.</p>

<p><strong>3.2.</strong> Garantir que todos os produtos e serviços ofertados:</p>
<ul>
  <li>sejam de produção própria, artesanal, independente ou de economia solidária;</li>
  <li>estejam em conformidade com a legislação vigente (ANVISA, INMETRO, direitos autorais, etc.);</li>
  <li>possuam preços e descrições fiéis à realidade.</li>
</ul>

<p><strong>3.3.</strong> Cumprir os pedidos realizados pela plataforma dentro do prazo informado no momento da venda.</p>

<p><strong>3.4.</strong> Tratar compradores com respeito, cordialidade e sem qualquer forma de discriminação.</p>

<p><strong>3.5.</strong> Nos eventos presenciais: ocupar apenas o espaço designado, montar e desmontar a banca nos horários estabelecidos, e zelar pela limpeza e organização do local.</p>

<p><strong>3.6.</strong> Não comercializar itens que:</p>
<ul>
  <li>promovam ódio, discriminação ou violência de qualquer natureza;</li>
  <li>sejam reproduções não autorizadas (pirataria);</li>
  <li>estejam fora do eixo declarado sem prévia autorização.</li>
</ul>

<hr>

<h3>4. DAS OBRIGAÇÕES DA FEIRA</h3>

<p><strong>4.1.</strong> Disponibilizar a loja virtual ao(à) EXPOSITOR(A) aprovado(a), com ferramentas de cadastro de produtos, gestão de pedidos e painel de desempenho.</p>

<p><strong>4.2.</strong> Processar os pagamentos das vendas realizadas pela plataforma e repassar ao(à) EXPOSITOR(A) o valor líquido (deduzida a taxa de serviço) em até 2 (dois) dias úteis após a confirmação do pagamento.</p>

<p><strong>4.3.</strong> Informar ao(à) EXPOSITOR(A) com antecedência mínima de 7 (sete) dias sobre qualquer alteração nas taxas de serviço ou nas regras de participação.</p>

<p><strong>4.4.</strong> Divulgar os perfis dos expositores ativos nos canais digitais da FEIRA (redes sociais, newsletter e homepage).</p>

<hr>

<h3>5. DAS TAXAS E COMISSÕES</h3>

<p><strong>5.1.</strong> A participação na plataforma é <strong>gratuita</strong> para cadastro e manutenção da loja virtual.</p>

<p><strong>5.2.</strong> Sobre cada venda concluída, a FEIRA retém uma <strong>taxa de serviço</strong> definida nas configurações da plataforma e informada ao(à) EXPOSITOR(A) no momento da aprovação. A taxa atual está disponível no painel do(a) lojista.</p>

<p><strong>5.3.</strong> Nos eventos presenciais, a taxa de ocupação do espaço físico é definida individualmente para cada evento e comunicada com antecedência.</p>

<hr>

<h3>6. DA VIGÊNCIA E DO CANCELAMENTO</h3>

<p><strong>6.1.</strong> Este contrato entra em vigor na data de aprovação do cadastro e permanece ativo enquanto a conta do(a) EXPOSITOR(A) estiver ativa na plataforma.</p>

<p><strong>6.2.</strong> O(A) EXPOSITOR(A) pode solicitar o encerramento da conta a qualquer momento, pelo painel ou pelo e-mail de atendimento da FEIRA.</p>

<p><strong>6.3.</strong> A FEIRA se reserva o direito de suspender ou encerrar a participação de EXPOSITOR(A) que:</p>
<ul>
  <li>descumprir qualquer cláusula deste contrato;</li>
  <li>receber reclamações reiteradas de compradores sem resolução;</li>
  <li>atuar em desacordo com os valores da economia solidária e da cultura popular.</li>
</ul>

<p><strong>6.4.</strong> Em caso de suspensão, o(a) EXPOSITOR(A) será notificado(a) previamente, exceto em situações de urgência que exijam ação imediata para proteção da comunidade.</p>

<hr>

<h3>7. DA PROPRIEDADE INTELECTUAL</h3>

<p><strong>7.1.</strong> O(A) EXPOSITOR(A) declara ser titular ou estar devidamente autorizado(a) a comercializar todos os itens cadastrados, respondendo civil e criminalmente por eventuais violações de direitos de terceiros.</p>

<p><strong>7.2.</strong> Ao cadastrar imagens, textos e descrições na plataforma, o(a) EXPOSITOR(A) autoriza a FEIRA a utilizá-los exclusivamente para fins de divulgação dos próprios produtos e da feira.</p>

<hr>

<h3>8. DA LGPD E DOS DADOS PESSOAIS</h3>

<p><strong>8.1.</strong> Os dados pessoais coletados (CPF/CNPJ, e-mail, WhatsApp, dados bancários) são utilizados exclusivamente para operação da plataforma, repasse de valores e comunicação com o(a) EXPOSITOR(A).</p>

<p><strong>8.2.</strong> CPF e CNPJ são armazenados de forma criptografada e não são repassados a terceiros sem consentimento explícito.</p>

<p><strong>8.3.</strong> O(A) EXPOSITOR(A) pode solicitar acesso, correção ou exclusão de seus dados a qualquer momento, nos termos da Lei 13.709/2018 (LGPD).</p>

<hr>

<h3>9. DO FORO</h3>

<p><strong>9.1.</strong> As partes elegem o foro da Comarca de São Paulo — SP para dirimir eventuais controvérsias oriundas deste instrumento, com renúncia expressa a qualquer outro, por mais privilegiado que seja.</p>

<hr>

<p>Ao concluir o cadastro e marcar a opção de aceite, o(a) EXPOSITOR(A) declara ter lido, compreendido e concordado com todos os termos deste contrato.</p>

<p><em>Feira Esquerda Livre — pelo direito de viver do próprio trabalho.</em></p>
HTML;

        SiteSetting::updateOrCreate(
            ['id' => 1],
            ['contrato_expositor' => $contrato]
        );
    }
}
