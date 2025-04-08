
# track job - uber
**!!! São dois projetos em um só !!!!**

Juntos apenas para facilitar o uso do servidor onde fica alocado em produção. 

O projeto **Track Job**, tem por objetivo o rastramento das candidaturas das vagas de emprego. 

O segundo projeto **Uber** utiliza diferentes ferramentas para registar os gastos e pagamentos com Uber familia.

Ambos foram desenvolvidos com php+mysql, sendo o ambiente de desenvolvimento em docker compose.
O objetivo principal era codar o mínimo possível, usando ao máximo o prompt com chatgpt.
Inicialmente foi executado em 3 dias, +- 20h (com intervalos), apenas track job. Atualmente não sei precisar quantas horas foram gastas em todo o projeto, mas estimo em torno de 70h.

O resultado final está rodando em um servidor remoto que tem php e mysql já instalado.

## Subindo Docker

💡Pré requisitos💡
Para subir o projeto localmente é necessário docker e docker-compose.
Ou pode rodar em um servidor com php e mysql.

Pode baixar o projeto inteiro e após descompactar, entra no diretório criado e executa o comando:
> docker compose up --build -d

*Dependendo da versão do docker compose é escrito docker-compose*

**up** Subir o serviço.

**--build** Para construir a imagem do php (devido a particularidades).

**-d** Para manter rodando em background.

Para validar que esta tudo rodando:
> docker ps

Se estiver tudo ok, pode acessar <http://localhost:8080>

## Baixando o serviço

Para baixar os serviços, ainda na pasta de trabalho, executa o comando:
> docker compose down

Se precisar limpar os volumes e zera tudo:
> docker compose down volume

# Biblitecas utilizadas

* Para frontend utilizado bootstrap;
* Alguns js como popper e jquery;
* Para busca de e-mail imap do próprio php (habilitado no dockerfile);
* Para envio do e-mail [PHPMailer](https://github.com/PHPMailer/PHPMailer);

Posso estar esquecendo de algo agora no momento da documentação, mas basicamente isso.

# Track Job

## Objetivo

Projeto para uso pessoal, não é um miniSaas ou algo assim, para registrar as vagas que me candidatei, pode ter mais usuários porém não existe vinculo do usuário a candidatura. Poderia ser feito esse vinculo, mas não era o objetivo.
Para evitar duplicidade é ultilizada o link da vaga como registro único.
Foram feitos alguns ajustes para o filtro da tela inicial, e um resumo das candidaturas.

### Autenticação

Apesar de estar com uma senha salva no código php ( *algo bem feio* ), existe a configuração de validação de senha salva criptografada pelo php. No entanto, o ideal ainda seria utilizar criptografia de base de dados, de forma que não pode ser recuperada a senha. Porem não foi criado sistema de registro e recuperação de senha, por isso a simplicidade.

### Expiração de sessão

Existe um scheduler na base para limpar a sessão, caso fique inativo por mais de 5 min. Apesar de no navegador do usuário ainda existir o hash da sessão, ela não será mais valida quando tentar atualizar a página, onde será encaminhando para tela de login.

## Próximos passos

- ~~Ajustar uns scripts que ficaram bagunçados~~
- ~~Limpar na base produtiva os users da base teste~~
- Pensar se revisar a senha ou não.

Alguns ajustes finos, mas não será trabalhado layout e outros detalhes.

# Uber

## Objetivo

O sistema é dividido em praticamente 2 etapas:
* Leitura dos e-mails de relatório do Uber com as viagens;
* Acesso do usuário para ver as viagens realizadas e baixa do pagamento pelo admin;

Para não precisar ficar guardando senha e fazendo gestão de usuário, o acesso se da por link enviado por e-mail. 
O envio de e-mail é limitado por alguns e-mail válidos, não e aberto ao publico geral. 
