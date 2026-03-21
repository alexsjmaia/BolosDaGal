# Sistema Web em PHP com MySQL

Este projeto cria uma tela de login simples em PHP usando MySQL.

## 1. O que foi criado

- `index.php`: tela de login
- `login.php`: valida usuario e senha no banco
- `dashboard.php`: area protegida apos login
- `cadastrar-item.php`: formulario para cadastro de itens
- `salvar-item.php`: grava os itens no banco de dados
- `alterar-item.php`: lista e permite editar ou excluir itens
- `atualizar-item.php`: salva alteracoes de um item
- `excluir-item.php`: remove um item do banco
- `vender-item.php`: tela para registrar venda de itens
- `adicionar-ao-carrinho.php`: adiciona um item ao carrinho da comanda
- `remover-do-carrinho.php`: remove um item do carrinho da comanda
- `salvar-venda.php`: grava todos os itens da comanda no banco
- `comprovante-venda.php`: gera o comprovante termico da venda para impressao
- `relatorio-itens-vendidos.php`: gera o ranking dos itens mais vendidos por periodo
- `listar-itens.php`: lista os itens cadastrados com markup
- `backup.php`: cria e restaura backups completos do sistema
- `logout.php`: encerra a sessao
- `db.php`: conexao com MySQL
- `config.php`: configuracao do banco
- `database.sql`: script para criar o banco e as tabelas `usuarios` e `itens`
- `criar-tabela-vendas.sql`: script para criar a tabela `vendas`
- `alterar-tabela-vendas-comanda.sql`: ajusta a tabela `vendas` existente para suportar comandas
- `installer\BolosDaGal.iss`: script do instalador Inno Setup

## 2. Instalar o PHP no Windows

### Opcao mais simples

1. Acesse o site oficial: [https://windows.php.net/download/](https://windows.php.net/download/)
2. Baixe a versao `PHP 8.x Thread Safe` em formato `.zip`.
3. Extraia para uma pasta, por exemplo: `C:\php`
4. Dentro da pasta do PHP, copie o arquivo `php.ini-development` e renomeie para `php.ini`
5. Abra o `php.ini` e habilite esta extensao removendo `;` do inicio da linha:

```ini
extension=pdo_mysql
```

6. Adicione `C:\php` ao `Path` do Windows.
7. Abra um novo terminal e teste:

```powershell
php -v
```

Se aparecer a versao do PHP, ele foi instalado corretamente.

## 3. Instalar o MySQL Server no Windows

Voce ja tem o MySQL Workbench, mas ele normalmente nao instala sozinho o servidor. Confira se o MySQL Server esta instalado:

1. Abra o menu Iniciar e procure por `MySQL Installer`
2. Se nao tiver, baixe em: [https://dev.mysql.com/downloads/installer/](https://dev.mysql.com/downloads/installer/)
3. Rode o instalador e escolha instalar o `MySQL Server`
4. Durante a instalacao:
   - Defina a porta padrao `3306`
   - Escolha autenticacao padrao
   - Defina a senha do usuario `root`
5. Finalize a instalacao e inicie o servico do MySQL

## 4. Configurar o MySQL

### Pelo MySQL Workbench

1. Abra o MySQL Workbench
2. Conecte com o servidor local
3. Abra o arquivo `database.sql`
4. Execute o script completo

Esse script vai:

- criar o banco `bolosdagal`
- criar a tabela `usuarios`
- criar a tabela `itens`
- criar a tabela `vendas`
- inserir o usuario `bolos` com a senha `dagal`

## 5. Ajustar a conexao do projeto

Abra o arquivo `config.php` e ajuste se necessario:

```php
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'bolosdagal',
    'db_user' => 'root',
    'db_pass' => '',
];
```

Se o seu MySQL `root` tiver senha, coloque a senha em `db_pass`.

## 6. Rodar o sistema no navegador

No terminal, entre na pasta do projeto:

```powershell
cd C:\BolosDaGal
```

Inicie o servidor interno do PHP:

```powershell
php -S localhost:8000
```

Depois abra no navegador:

[http://localhost:8000](http://localhost:8000)

## 7. Login do sistema

- Usuario: `bolos`
- Senha: `dagal`

## 8. Observacao importante

Para facilitar o primeiro teste, a senha foi salva em texto simples no banco. Em um sistema real, o ideal e usar `password_hash()` e `password_verify()` no PHP.

## 9. Cadastro de itens

Depois de fazer login, o sistema exibe um menu com a opcao `1. Cadastrar Item`.

Nessa tela e possivel salvar:

- codigo do produto
- nome do produto
- NCM
- preco de custo
- preco de venda

Todos esses dados sao gravados na tabela `itens` do MySQL.

## 10. Alteracao e exclusao de itens

No menu principal existe tambem a opcao `2. Alterar Item`.

Nessa tela voce pode:

- escolher um item cadastrado
- alterar nome do produto
- alterar NCM
- alterar preco de custo
- alterar preco de venda
- excluir o item

O codigo do produto fica bloqueado para edicao.

## 11. Estrutura de vendas

O menu principal agora exibe a opcao `3. Vender Item`.

Para preparar o banco de dados da venda, foi criado o arquivo [criar-tabela-vendas.sql](C:\BolosDaGal\criar-tabela-vendas.sql) com a tabela `vendas`.

Campos principais da venda:

- codigo do item
- descricao do item
- quantidade
- data e hora da venda
- valor total

Campos adicionais incluidos:

- `comanda_codigo`: identifica todos os itens da mesma venda
- `item_id`: liga a venda ao cadastro do item
- `preco_unitario`: guarda o valor do item no momento da venda
- `forma_pagamento`: guarda como a venda foi paga
- `valor_recebido`: guarda o valor entregue no pagamento em dinheiro
- `valor_troco`: guarda o troco devolvido
- `usuario_login`: guarda quem registrou a venda
- `observacao`: permite observacoes futuras na venda

## 12. Comanda com varios itens

Na tela `3. Vender Item`, agora voce pode:

- adicionar varios itens ao carrinho
- somar quantidades do mesmo item
- remover itens do carrinho
- escolher a forma de pagamento
- informar o valor recebido quando for dinheiro
- visualizar o troco na tela
- finalizar todos os itens de uma vez

Quando a venda e finalizada, o sistema grava uma linha por item na tabela `vendas`, todas com o mesmo `comanda_codigo`.

Ao finalizar a venda, o sistema abre um comprovante termico pronto para impressao.

## 13. Relatorio de itens mais vendidos

No menu principal existe tambem a opcao `4. Itens Mais Vendidos`.

Nessa tela voce informa:

- data inicial
- data final

O sistema exibe um relatorio por periodo com:

- codigo do item
- descricao
- quantidade vendida
- valor total do custo
- valor total de venda
- lucro bruto

No final do relatorio e mostrada a soma geral de custo, venda e lucro bruto.

## 14. Backup e restauracao

No painel principal existe um botao separado chamado `Backup e Restauracao`.

Nessa tela voce pode:

- criar um backup completo dos arquivos do sistema
- salvar uma copia do banco de dados
- restaurar um backup anterior

Os backups ficam em `C:\BolosDaGal\backup`.

## 15. Listar itens cadastrados

No menu principal existe tambem a opcao `5. Listar Itens Cadastrados`.

Nessa tela o sistema mostra:

- codigo
- descricao
- preco de custo
- preco de venda
- markup

O markup e calculado por `preco de venda / preco de custo`.

## 16. Instalador Inno Setup

Foi criado o arquivo [installer\BolosDaGal.iss](C:\BolosDaGal\installer\BolosDaGal.iss) para gerar um instalador Windows do sistema.

Esse instalador:

- instala o sistema em `C:\BolosDaGal`
- cria a pasta `C:\BolosDaGal\backup`
- pede o caminho do `php.exe`
- pede os dados do MySQL
- recria o arquivo `config.php`
- tenta importar o arquivo `database.sql` se o `mysql.exe` for informado

Tambem foi criado o arquivo [installer\LEIA-ME-INSTALADOR.txt](C:\BolosDaGal\installer\LEIA-ME-INSTALADOR.txt) com orientacoes rapidas de uso.
