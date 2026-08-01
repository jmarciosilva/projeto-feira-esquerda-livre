<?php

// Conteúdo dos modais de ajuda contextual dos painéis internos (admin, lojista, cliente).
// A chave segue exatamente os segmentos do nome da rota Laravel (ex.: rota "admin.pages.index"
// -> help.admin.pages.index), para que a busca seja um simples config("help.$routeName").
// Formulários que compartilham o mesmo componente Livewire para criar/editar reaproveitam
// a mesma variável de conteúdo abaixo.

$usuarioForm = [
    'title' => 'Cadastro de Usuário Interno',
    'intro' => 'Cadastre ou edite um membro da equipe interna e defina seu papel de acesso.',
    'tips' => [
        'Escolha o papel com cuidado: ele define quais áreas do painel a pessoa vai enxergar.',
        'Se o e-mail já pertence a um cliente, o sistema oferece manter o histórico de pedidos dessa pessoa.',
        'Você pode desativar o acesso de alguém sem apagar seu histórico.',
    ],
];

$paginaForm = [
    'title' => 'Formulário de Página',
    'intro' => 'Crie ou edite uma página institucional do site, como Sobre Nós ou Como Funciona.',
    'tips' => [
        'O endereço (slug) da página é gerado automaticamente a partir do título.',
        'Use as seções para montar o conteúdo em blocos, sem precisar mexer em código.',
        'Salve e visite o link da página no site público para conferir o resultado.',
    ],
];

$bannerForm = [
    'title' => 'Formulário de Banner',
    'intro' => 'Cadastre ou edite um banner exibido no carrossel da página inicial.',
    'tips' => [
        'Use imagens no formato indicado para não ficarem cortadas ou distorcidas.',
        'O campo de link é opcional — preencha se quiser levar o visitante a outra página.',
        'A ordem de exibição pode ser ajustada pelo campo de posição.',
    ],
];

$postForm = [
    'title' => 'Formulário de Post',
    'intro' => 'Crie ou edite uma notícia, post de blog ou campanha editorial que aparece no site público.',
    'tips' => [
        'Escreva um título chamativo — ele também vira o link (slug) da publicação.',
        'Adicione uma imagem de destaque para o post ficar mais atrativo na listagem.',
        'Publique somente quando o conteúdo estiver revisado.',
    ],
];

$eventForm = [
    'title' => 'Formulário de Evento',
    'intro' => 'Cadastre ou edite uma feira: data, local, banner, capacidade de expositores e destaque na home.',
    'tips' => [
        'Preencha a data e o endereço com atenção — eles aparecem na agenda pública.',
        'Marque "Destaque" para o evento aparecer com prioridade na página inicial.',
        'A capacidade de expositores controla quantas vagas aparecem como disponíveis.',
    ],
];

$categoriaForm = [
    'title' => 'Formulário de Categoria',
    'intro' => 'Crie ou edite uma categoria usada para organizar produtos, serviços ou cuidados no catálogo.',
    'tips' => [
        'Escolha o eixo (Produto, Serviço ou Cuidado) certo — ele decide onde a categoria aparece nos filtros.',
        'Nomes curtos e claros funcionam melhor nos filtros do catálogo público.',
    ],
];

$campaignForm = [
    'title' => 'Campanha de E-mail',
    'intro' => 'Monte uma campanha de e-mail marketing: assunto, conteúdo, destinatários e envio.',
    'tips' => [
        'Escolha o público certo — assinantes da newsletter, clientes ou uma lista manual de e-mails.',
        'Use o botão de enviar teste para revisar como o e-mail chega antes do envio real.',
        'O link de descadastro é inserido automaticamente no rodapé, conforme exigido pela LGPD.',
    ],
];

$produtoForm = [
    'title' => 'Cadastro de Produto',
    'intro' => 'Cadastre ou edite um item do seu catálogo: pode ser um produto físico, um serviço ou um cuidado/bem viver.',
    'tips' => [
        'Escolha o eixo certo no topo do formulário — ele define quais campos aparecem a seguir.',
        'Envie até 4 fotos; o sistema comprime e otimiza automaticamente para carregar rápido no celular.',
        'Para serviços e cuidados, preencha modalidade, tipo de preço e duração estimada.',
        'Use a seção de Perguntas Frequentes para responder de antemão dúvidas comuns dos clientes.',
    ],
];

$enderecoForm = [
    'title' => 'Endereço de Entrega',
    'intro' => 'Cadastre ou edite um endereço para usar nas suas compras.',
    'tips' => [
        'Preencha o CEP corretamente — ele é usado para calcular o frete.',
        'Você pode cadastrar mais de um endereço e escolher qual usar em cada compra.',
    ],
];

return [

    'admin' => [
        'dashboard' => [
            'title' => 'Painel Principal',
            'intro' => 'Aqui você tem uma visão geral rápida da plataforma: pedidos, lojistas e conteúdo em um só lugar.',
            'tips' => [
                'Use o menu lateral à esquerda para navegar entre as áreas do painel.',
                'Os números apresentados são atualizados automaticamente conforme o sistema é usado.',
                'Se não vir alguma opção no menu, pode ser que seu perfil não tenha permissão para aquela área.',
            ],
        ],

        'settings' => [
            'edit' => [
                'title' => 'Configurações Gerais',
                'intro' => 'Aqui ficam os dados básicos do site: nome, logotipo, favicon e outras informações exibidas em todo o site público.',
                'tips' => [
                    'Envie o logotipo e o favicon em boa resolução para não ficarem borrados.',
                    'Depois de salvar, abra o site público em outra aba para conferir as mudanças.',
                    'Alterações aqui afetam o site inteiro — revise com atenção antes de salvar.',
                ],
            ],
            'mail' => [
                'title' => 'Configurações de E-mail',
                'intro' => 'Defina o servidor SMTP usado para enviar e-mails automáticos: confirmação de pedido, contato, campanhas e certificados do curso.',
                'tips' => [
                    'Preencha host, porta, usuário e senha exatamente como fornecidos pelo seu provedor de e-mail.',
                    'Depois de configurar, envie um e-mail de teste antes de confiar no envio automático.',
                    'Sem essas configurações corretas, o site não consegue avisar clientes e lojistas por e-mail.',
                ],
            ],
            'checkout' => [
                'title' => 'Frete & Pagamento',
                'intro' => 'Configure as regras de frete manual, a mensagem exibida no checkout, a comissão da plataforma e as credenciais do Melhor Envio e Mercado Pago.',
                'tips' => [
                    'A comissão (%) é usada para relatório — hoje o repasse entre loja e plataforma ainda é manual.',
                    'As credenciais do Mercado Pago e Melhor Envio ficam guardadas aqui e são usadas automaticamente no checkout.',
                    'Teste um pedido de ponta a ponta depois de alterar essas configurações.',
                ],
            ],
        ],

        'usuarios' => [
            'index' => [
                'title' => 'Usuários Internos',
                'intro' => 'Lista de todas as pessoas da equipe interna com acesso ao painel administrativo.',
                'tips' => [
                    'Use a busca para encontrar rapidamente por nome, e-mail ou papel.',
                    'O botão "Novo Usuário" cadastra um novo membro da equipe.',
                    'Usuários inativos não conseguem mais entrar no painel, mas continuam na lista.',
                ],
            ],
            'create' => $usuarioForm,
            'edit' => $usuarioForm,
        ],

        'permissoes' => [
            'index' => [
                'title' => 'Perfis de Acesso',
                'intro' => 'Aqui você controla quais permissões cada papel (administrador, gerente, supervisor, editor) possui dentro do painel.',
                'tips' => [
                    'Marcar uma permissão libera aquela ação para todos os usuários daquele papel.',
                    'O administrador sempre tem acesso total — essa permissão não pode ser removida.',
                    'Mudanças aqui valem imediatamente para todos os usuários daquele papel.',
                ],
            ],
        ],

        'pages' => [
            'index' => [
                'title' => 'Páginas',
                'intro' => 'Lista de todas as páginas institucionais do site (Sobre, Como Funciona, etc.).',
                'tips' => [
                    'Clique em uma página para editar o conteúdo.',
                    'O botão "Nova Página" cria uma página institucional do zero.',
                    'Páginas ficam acessíveis publicamente pelo endereço mostrado na listagem.',
                ],
            ],
            'create' => $paginaForm,
            'edit' => $paginaForm,
        ],

        'banners' => [
            'index' => [
                'title' => 'Banners',
                'intro' => 'Lista dos banners que aparecem no carrossel da página inicial do site.',
                'tips' => [
                    'Banners ativos aparecem em rotação automática na home.',
                    'Você pode reordenar, ativar/desativar ou excluir um banner existente.',
                    'O botão "Novo Banner" adiciona mais uma imagem ao carrossel.',
                ],
            ],
            'create' => $bannerForm,
            'edit' => $bannerForm,
        ],

        'menus' => [
            'index' => [
                'title' => 'Menus',
                'intro' => 'Gerencie os itens de navegação exibidos no menu do site público.',
                'tips' => [
                    'Clique em um item de menu para editar o texto ou o link.',
                    'A ordem dos itens na lista é a mesma ordem exibida no menu do site.',
                    'Alterações aqui refletem imediatamente na navegação pública.',
                ],
            ],
            'edit' => [
                'title' => 'Editar Item de Menu',
                'intro' => 'Altere o texto exibido e o destino (link) de um item do menu de navegação.',
                'tips' => [
                    'Confira se o link de destino está correto antes de salvar.',
                    'O texto exibido deve ser curto para caber bem no menu, especialmente no celular.',
                ],
            ],
        ],

        'media' => [
            'index' => [
                'title' => 'Biblioteca de Mídia',
                'intro' => 'Todos os arquivos de imagem enviados para o site ficam organizados aqui, prontos para reutilizar em páginas, posts e banners.',
                'tips' => [
                    'Envie novas imagens pelo botão de upload — o sistema já comprime automaticamente.',
                    'Use a busca para encontrar um arquivo já enviado antes de subir um novo.',
                    'Evite excluir arquivos que ainda estão em uso em alguma página ou banner.',
                ],
            ],
        ],

        'posts' => [
            'index' => [
                'title' => 'Posts',
                'intro' => 'Lista de notícias, posts e campanhas editoriais publicadas ou em rascunho no site.',
                'tips' => [
                    'Use os filtros para encontrar posts publicados ou ainda em rascunho.',
                    'O botão "Novo Post" cria uma publicação do zero.',
                    'Posts em rascunho não aparecem para o público até serem publicados.',
                ],
            ],
            'create' => $postForm,
            'edit' => $postForm,
        ],

        'events' => [
            'index' => [
                'title' => 'Eventos',
                'intro' => 'Lista de todas as feiras cadastradas, passadas e futuras, exibidas na agenda pública.',
                'tips' => [
                    'Clique em um evento para editar data, local ou expositores confirmados.',
                    'O botão "Novo Evento" cadastra uma nova feira na agenda.',
                    'Eventos com data passada continuam na lista para histórico.',
                ],
            ],
            'create' => $eventForm,
            'edit' => $eventForm,
        ],

        'expositores' => [
            'index' => [
                'title' => 'Expositores',
                'intro' => 'Lista de todas as lojas (expositores) cadastradas na plataforma.',
                'tips' => [
                    'Clique em um expositor para editar seus dados ou status.',
                    'Novos expositores entram automaticamente aqui quando uma solicitação é aprovada em "Solicitações".',
                    'Use esta tela para consultar rapidamente os dados de contato de um lojista.',
                ],
            ],
            'edit' => [
                'title' => 'Editar Expositor',
                'intro' => 'Altere os dados públicos e financeiros de um expositor: logo, banner, descrição, cidade, redes sociais e status.',
                'tips' => [
                    'O status controla se a loja aparece ou não no site público.',
                    'Cidade e estado são usados para calcular o frete e localizar a loja na agenda.',
                    'Ative "Exibir na Home" para a loja participar da rotação de expositores na página inicial.',
                ],
            ],
            'visibilidade' => [
                'title' => 'Visibilidade na Home',
                'intro' => 'Controle quais expositores aparecem em destaque ou em rotação democrática na página inicial, e acompanhe as impressões de cada um.',
                'tips' => [
                    'Destaques pagos aparecem sempre primeiro, dentro do período configurado.',
                    'Expositores sem destaque entram num sorteio de rotação — ajuste o peso para dar mais ou menos prioridade.',
                    'O gráfico de impressões mostra quantas vezes cada loja foi exibida nos últimos 30 dias.',
                ],
            ],
        ],

        'categorias' => [
            'index' => [
                'title' => 'Categorias',
                'intro' => 'Lista de categorias usadas para organizar o catálogo de produtos, serviços e cuidados.',
                'tips' => [
                    'Cada categoria pertence a um eixo específico (Produto, Serviço ou Cuidado).',
                    'O botão "Nova Categoria" cria mais uma opção de filtro para os lojistas usarem.',
                    'Categorias sem produtos vinculados não aparecem nos filtros do site público.',
                ],
            ],
            'create' => $categoriaForm,
            'edit' => $categoriaForm,
        ],

        'lojistas' => [
            'solicitacoes' => [
                'title' => 'Solicitações de Lojistas',
                'intro' => 'Analise pedidos de pessoas que querem se tornar expositores na plataforma.',
                'tips' => [
                    'Use os filtros Pendente / Aprovado / Bloqueado para organizar o trabalho.',
                    'Ao aprovar, o sistema cria automaticamente o acesso de lojista e a loja vinculada.',
                    'Ao bloquear, escreva o motivo — isso fica registrado para consulta futura.',
                ],
            ],
        ],

        'pedidos' => [
            'index' => [
                'title' => 'Pedidos',
                'intro' => 'Visão geral de todos os pedidos feitos na plataforma, com status de pagamento e entrega.',
                'tips' => [
                    'Use os filtros para localizar pedidos por status, loja ou período.',
                    'Clique em um pedido para ver detalhes completos: itens, cliente, pagamento e envio.',
                    'A confirmação de pagamento de cada loja é feita pelo próprio lojista — aqui é só acompanhamento.',
                ],
            ],
        ],

        'clientes' => [
            'index' => [
                'title' => 'Clientes',
                'intro' => 'Lista de todas as pessoas cadastradas como clientes do marketplace.',
                'tips' => [
                    'Use a busca para localizar um cliente por nome ou e-mail.',
                    'Você pode inativar um cliente no marketplace sem afetar outros acessos que ele tenha.',
                    'Esta lista não inclui a equipe interna — isso fica em "Usuários Internos".',
                ],
            ],
        ],

        'feed' => [
            'reportes' => [
                'title' => 'Moderação da Comunidade',
                'intro' => 'Revise publicações do feed social que foram denunciadas por outros usuários.',
                'tips' => [
                    'Analise o conteúdo denunciado antes de tomar uma ação.',
                    'Você pode ocultar uma publicação abusiva diretamente por aqui.',
                    'Toda ação de moderação fica registrada, com data e responsável.',
                ],
            ],
        ],

        'email-marketing' => [
            'index' => [
                'title' => 'Email Marketing',
                'intro' => 'Lista de todas as campanhas de e-mail criadas, com status e métricas de envio.',
                'tips' => [
                    'Só campanhas em rascunho podem ser editadas ou excluídas.',
                    'Use "Duplicar" para reaproveitar uma campanha já criada como ponto de partida.',
                    'Clique em uma campanha enviada para ver o relatório de abertura e cliques.',
                ],
            ],
            'create' => $campaignForm,
            'edit' => $campaignForm,
            'report' => [
                'title' => 'Relatório da Campanha',
                'intro' => 'Acompanhe o desempenho de uma campanha enviada: entregues, abertos, cliques e descadastros.',
                'tips' => [
                    'A taxa de abertura ajuda a avaliar se o assunto do e-mail foi atrativo.',
                    'Descadastros são automáticos e definitivos — a pessoa não recebe mais campanhas futuras.',
                ],
            ],
        ],
    ],

    'lojista' => [
        'dashboard' => [
            'title' => 'Painel da Loja',
            'intro' => 'Resumo rápido da sua loja: produtos ativos, pedidos recentes e avisos importantes.',
            'tips' => [
                'Use o menu lateral para acessar produtos, pedidos, comunidade e cursos.',
                'Fique de olho nos badges vermelhos e amarelos do menu — eles avisam sobre perguntas ou mensagens pendentes.',
            ],
        ],

        'loja' => [
            'title' => 'Minha Loja',
            'intro' => 'Configure a aparência pública da sua loja: logotipo, banner, descrição, redes sociais e localização.',
            'tips' => [
                'Envie logotipo em formato quadrado e banner em formato retangular para não ficarem cortados.',
                'Preencha cidade e estado corretamente — eles são usados para calcular o frete dos seus produtos.',
                'A descrição é a primeira coisa que o cliente lê ao visitar sua loja — capriche.',
            ],
        ],

        'produtos' => [
            'index' => [
                'title' => 'Meus Produtos',
                'intro' => 'Lista de todos os produtos, serviços e cuidados cadastrados na sua loja.',
                'tips' => [
                    'Use o botão de ativar/desativar para tirar um item da vitrine sem precisar excluir.',
                    'O botão "Novo Produto" abre o formulário de cadastro.',
                    'Produtos inativos não aparecem para os clientes, mas continuam salvos aqui.',
                ],
            ],
            'create' => $produtoForm,
            'edit' => $produtoForm,
        ],

        'feed' => [
            'index' => [
                'title' => 'Comunidade',
                'intro' => 'Publique fotos, novidades e avisos da sua loja no feed social visto por toda a comunidade.',
                'tips' => [
                    'Publicações podem ter até 4 fotos e um texto curto.',
                    'Curtidas e comentários aparecem em tempo real, sem precisar atualizar a página.',
                    'Evite textos muito longos — publicações curtas têm mais engajamento.',
                ],
            ],
        ],

        'pedidos' => [
            'index' => [
                'title' => 'Meus Pedidos',
                'intro' => 'Acompanhe os pedidos recebidos pela sua loja: pagamento, envio e status de entrega.',
                'tips' => [
                    'Confirme o recebimento do pagamento assim que identificar o PIX ou depósito do cliente.',
                    'Use "Marcar como Enviado" para informar transportadora e código de rastreio.',
                    'O botão de chat abre a conversa com o cliente daquele pedido.',
                ],
            ],
            'chat' => [
                'title' => 'Chat do Pedido',
                'intro' => 'Converse diretamente com o cliente sobre este pedido específico.',
                'tips' => [
                    'As mensagens são atualizadas automaticamente a cada poucos segundos.',
                    'Use este canal para combinar detalhes de entrega ou tirar dúvidas sobre o pedido.',
                    'O cliente só vê a conversa referente a este pedido, não outras lojas.',
                ],
            ],
        ],

        'exposicao' => [
            'index' => [
                'title' => 'Exposição na Home',
                'intro' => 'Veja quantas vezes sua loja apareceu na vitrine da página inicial do site.',
                'tips' => [
                    'O número de impressões mostra quantas vezes sua loja foi exibida para visitantes.',
                    'Se sua loja está em rotação democrática, ela concorre igualmente com as demais lojas participantes.',
                    'Fale com a administração se quiser contratar um destaque com posição garantida.',
                ],
            ],
        ],

        'perguntas' => [
            'index' => [
                'title' => 'Perguntas',
                'intro' => 'Responda perguntas públicas que clientes fizeram sobre seus produtos.',
                'tips' => [
                    'Use os filtros Aguardando / Respondidas para organizar seu trabalho.',
                    'Perguntas respondidas ficam visíveis para todos os visitantes da página do produto.',
                    'Você pode ocultar uma pergunta inadequada usando o botão de visibilidade.',
                ],
            ],
        ],

        'ava' => [
            'index' => [
                'title' => 'Meus Cursos',
                'intro' => 'Gerencie os cursos digitais vinculados aos seus produtos: módulos, aulas e alunos matriculados.',
                'tips' => [
                    'Marque um produto como "digital" no cadastro de produto para criar um curso automaticamente.',
                    'Um curso só fica visível para compra depois de publicado.',
                    'Clique em "Editar Curso" para abrir o construtor de módulos e aulas.',
                ],
            ],
            'builder' => [
                'title' => 'Construtor de Curso',
                'intro' => 'Monte a estrutura do seu curso: configurações gerais, módulos e aulas, incluindo vídeos e materiais para download.',
                'tips' => [
                    'Organize o conteúdo em módulos e, dentro deles, em aulas — a ordem pode ser ajustada.',
                    'Aulas aceitam vídeo (YouTube/Vimeo), texto, PDF ou áudio.',
                    'Ative o certificado se quiser que o aluno receba um PDF automático ao concluir o curso.',
                    'Só publique quando todo o conteúdo estiver pronto — cursos não publicados não aparecem para compra.',
                ],
            ],
        ],
    ],

    'cliente' => [
        'pedidos' => [
            'index' => [
                'title' => 'Meus Pedidos',
                'intro' => 'Acompanhe todos os pedidos que você já fez na plataforma, incluindo pagamento e entrega.',
                'tips' => [
                    'Clique em um pedido para ver detalhes de pagamento, itens e envio por loja.',
                    'Use o chat dentro do pedido para falar diretamente com o lojista.',
                    'Você pode acompanhar o rastreio de entrega diretamente pela página do pedido.',
                ],
            ],
        ],

        'enderecos' => [
            'index' => [
                'title' => 'Meus Endereços',
                'intro' => 'Lista dos endereços salvos para agilizar suas próximas compras.',
                'tips' => [
                    'O botão "Novo Endereço" cadastra mais um local de entrega.',
                    'Endereços salvos aparecem prontos para seleção rápida no checkout.',
                ],
            ],
            'create' => $enderecoForm,
            'edit' => $enderecoForm,
        ],

        'ava' => [
            'index' => [
                'title' => 'Meu Aprendizado',
                'intro' => 'Veja todos os cursos digitais em que você está matriculado e acompanhe seu progresso.',
                'tips' => [
                    'Clique em "Continuar" para retomar o curso de onde você parou.',
                    'A barra de progresso mostra quantas aulas você já concluiu.',
                    'Cursos concluídos liberam automaticamente o certificado em PDF para download.',
                ],
            ],
            'player' => [
                'title' => 'Player do Curso',
                'intro' => 'Assista às aulas do curso, baixe materiais complementares e acompanhe seu progresso.',
                'tips' => [
                    'Use o menu lateral do player para navegar entre os módulos e aulas.',
                    'Marque cada aula como concluída para avançar seu progresso no curso.',
                    'Materiais de apoio (PDFs, arquivos) ficam disponíveis para download abaixo de cada aula.',
                    'Ao completar 100% do curso, o certificado é gerado automaticamente.',
                ],
            ],
        ],
    ],

];
