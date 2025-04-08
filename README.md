
# job_apply
Projeto desenvolvido em php+mysql (com docker-compose) para salvar as candidaturas.

O objetivo principal era codar o mínimo possível, usando ao máximo o prompt com chatgpt.

Executado em 3 dias, +- 20h (com intervalos).

O resultado final está rodando em um servidor remoto que tem php e mysql já instalado.

## Subindo Docker

💡Pré requisitos💡
Para subir o projeto localmente é necessário docker e docker-compose.
Ou pode rodar em um servidor com php e mysql.

Pode baixar o projeto inteiro e após descompactar, entra no diretório criado e executa o comando:
> docker compose up --build -d

*Dependendo da versão do docker compose é escrito docker-compose*

** up ** Subir o serviço.

** --build ** Para construir a imagem do php (devido a particularidades).

** -d ** Para manter rodando em background.

Para validar que esta tudo rodando:
> docker ps

Se estiver tudo ok, pode acessar <http://localhost:8080>

## Baixando o serviço

Para baixar os serviços, ainda na pasta de trabalho, executa o comando:
> docker compose down

Se precisar limpar os volumes e zera tudo:
> docker compose down volume

# Curiosidades

Alguns pontos interessantes do projeto

## Autenticação

Apesar de estar com uma senha salva no código php ( *algo bem feio* ), existe a configuração de validação de senha salva criptografada  pelo php. No entanto, o ideal ainda seria utilizar criptografia de base, de forma que não pode ser recuperada a senha, mas não foi criado sistema de registro de senha, por isso a simplicidade.

## Expiração de sessão

Existe um scheduler na base para limpar a sessão caso fique inativo por mais de 5 min, apesar de para o navegador do usuário ainda existir o hash da sessão, ela não será mais valida quando tentar atualizar a página, encaminhando para tela de login.

# Próximos passos

- Ajustar uns scripts que ficaram bagunçados
- Limpar na base produtiva os users da base teste
- Pensar se revisar a senha ou não.

Alguns ajustes finos, mas não será trabalhado layout e outros detalhes.

