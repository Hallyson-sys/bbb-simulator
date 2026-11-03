# BBB Simulator — Modo DEV 3.0

## Como abrir

1. Inicie XAMPP (Apache) e abra uma temporada de **teste** no BBB Simulator.
2. Acesse `http://localhost/bbb-simulator/modo_teste.php` (ou use o atalho DEV existente).
3. Na seção **Modo DEV 3.0 — Diagnósticos**, escolha:
   - **Executar bateria completa:** inspeção do save + 22 testes de regras isolados.
   - **Verificar save atual:** apenas checagens dos dados da temporada aberta.
   - **Simular 22 regras:** fixtures isoladas, sem mexer nos participantes do seu save.
4. Interprete o relatório: **OK** = a verificação passou; **AVISO** = precisa conferir o estado; **PROBLEMA** = inconsistência encontrada.

## Executar testes automáticos no terminal

Na raiz do projeto, com PHP configurado no PATH:

```bash
php testes/dev3_regressao.php
```

No Windows com XAMPP, se `php` não estiver no PATH:

```powershell
C:\xampp\php\php.exe testes\dev3_regressao.php
```

O script verifica as regras simuladas e executa `php -l` em **todos** os arquivos PHP do projeto. Ele retorna código `0` se os testes passarem e código `1` se algum falhar, podendo ser usado em um script de deploy. Para executar apenas as simulações: `php testes/dev3_regressao.php --sem-lint`.

## O que os diagnósticos cobrem

- Integridade do elenco, fase, nomes, líder, Paredão, imunidades e alianças.
- Top 9, Top 8 sem Anjo/Discórdia e Top 5 sem dinâmicas.
- Paredão Falso sem antecipar o Top 8.
- Votação Turbinada, Carta Misteriosa, Imunidade Surpresa e Paredão Especial.
- Idempotência do registro de efeitos de dinâmicas (recarregar não deve publicar duas vezes).
- Proteção do colíder e peso de aliança.
- Prova do Top 4 (energia/risco, proteção contra respostas repetidas ou token inválido).
- Função usada pela Rotina/Fofocas para excluir participantes eliminados.

## Segurança e limitações

O DEV só abre no **localhost**, como na versão anterior. Os diagnósticos **não alteram o estado da partida**, e as simulações substituem `$_SESSION` por um cenário fictício apenas durante a execução de cada teste, com restauração em `finally`. Somente o **relatório** fica salvo na sessão para exibição.

Os laboratórios antigos de fase, moedas e dinâmicas **continuam modificando o save** quando acionados; faça um backup antes de usá-los.

Uma bateria verde **não garante uma partida completa sem bugs**. Ela não simula a interface mobile, o clique real do Safari, todas as combinações de poderes nem a votação de ponta a ponta; esses fluxos ainda exigem testes de jogo.

Para CI/deploy, configure `php testes/dev3_regressao.php` como uma etapa de verificação antes de fazer o push. Não use o painel DEV em produção.
