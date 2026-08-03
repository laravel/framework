# Console Dumps (`doc`)

## Objetivo

Criar uma forma simples de enviar valores de qualquer processo da aplicação Laravel para um console dedicado, sem interromper a execução do código.

A experiência deve ser semelhante à do `dump()`: o conteúdo será exibido de forma legível e bem formatada, preferencialmente com o arquivo e a linha de origem. A diferença é que a saída será centralizada em um comando próprio, permitindo receber dumps de requisições HTTP, filas e comandos Artisan no mesmo lugar.

## Funcionamento esperado

1. O helper `doc()` recebe o valor e obtém ou instancia um cliente.
2. O cliente envia o dump para um servidor local por meio de uma conexão TCP.
3. Um comando Artisan mantém o servidor ativo e recebe os dumps.
4. Um renderer restaura e exibe o conteúdo no terminal usando a formatação do Symfony VarDumper e os padrões visuais do Laravel.

## Decisões de nomenclatura

- Funcionalidade: **Console Dumps**
- Namespace: `Illuminate\Foundation\ConsoleDumps`
- Comando: `php artisan dump:listen`
- Identificador no comando `dev`: `dumps`
- Branch: `feature/console-dumps`

## Requisitos

- A implementação deve ser minimalista e não pode adicionar dependências.
- O cliente e o servidor devem ficar em `Illuminate\Foundation\ConsoleDumps` e se comunicar diretamente via TCP.
- A conexão deve ser rápida, não bloqueante e segura contra falhas. A indisponibilidade do servidor nunca deve interromper ou afetar a aplicação que chamou o helper.
- A serialização deve preservar o conteúdo necessário para que o dump seja restaurado e formatado corretamente no terminal.
- Sempre que possível, o dump deve incluir o arquivo e a linha em que o helper foi chamado, seguindo o comportamento atual do `dump()`.
- O comando deve ser compatível funcional e visualmente com o comando `dev`, ao lado de processos como servidor, filas, logs e Vite.
- A estrutura, a organização e o estilo do código devem seguir as convenções existentes no Laravel para aumentar as chances de aceitação do futuro pull request.
- O [Laravel Ray](https://github.com/spatie/laravel-ray) pode servir como referência para decisões que já tenham sido bem resolvidas, sem criar acoplamento ou copiar complexidade desnecessária.
- Todas as interações, nomenclaturas e mensagens presentes no código devem estar em inglês.
- Branches, arquivos, mensagens de commit e demais artefatos devem usar somente nomenclaturas relacionadas ao projeto e à funcionalidade.

## Etapas

Executar uma etapa por vez e iniciar a seguinte somente após a conclusão da anterior.

- [x] Escolher um nome adequado e compatível com a intenção da funcionalidade.
- [x] Criar a estrutura-base de diretórios.
- [x] Criar o servidor e o cliente.
- [x] Criar o comando Artisan.
- [x] Criar o helper `doc()`.
- [x] Revisar a implementação, a documentação e os testes.
