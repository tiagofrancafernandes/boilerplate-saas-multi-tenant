# 📋 Sistema de Gestão de Tarefas

Este diretório centraliza toda a gestão de tarefas, planos e rascunhos do projeto. O sistema utiliza um workflow baseado em estados para rastrear o progresso do desenvolvimento.

---

## 🚀 Ordem Obrigatória de Leitura

Antes de iniciar qualquer tarefa, você **DEVE** ler os seguintes arquivos na ordem especificada:

1. **README.md** (raiz do projeto)
   - Visão geral do projeto, tecnologias e quick start

2. **AGENTS.md**
   - Diretrizes para agentes de IA que trabalham no projeto
   - Comportamentos esperados e restrições

3. **CLAUDE.md**
   - Instruções globais e preferências de desenvolvimento
   - Convenções de codificação específicas do projeto

4. **ARCHITECTURE.md**
   - Arquitetura do sistema e padrões de design
   - Fluxos de dados e componentes principais

5. **BUSINESS_ANALYSIS.md**
   - Análise de negócio e requisitos funcionais
   - Workflows de usuário e critérios de sucesso

6. **UNIVERSAL-CODE-STYLE-RULES.md**
   - Regras obrigatórias de estilo de código
   - Padrões de formatação e boas práticas

Este padrão deve ser mantido em **planos, código, testes e documentação**.

---

## 📁 Estrutura de Pastas

```
tasks/
├── plans/              # Planos de desenvolvimento e épicas
├── doing/              # Tarefas em andamento
├── done/               # Tarefas concluídas
├── paused/             # Tarefas pausadas ou interrompidas
├── drafts/             # Rascunhos e ideias preliminares
└── .ignore/            # Arquivos ignorados pelo versionamento
```

---

## 📌 Estados das Tarefas

Uma tarefa progride entre os seguintes estados:

```
DRAFT → PLANNING → DOING → DONE
           ↓
        PAUSED → DOING (retoma)
           ↓
         DONE
```

### Estados Detalhados

| Estado | Pasta | Descrição |
|--------|-------|-----------|
| **Draft** | `drafts/` | Ideias preliminares, não validadas ainda |
| **Planning** | `plans/` | Plano validado, aguardando execução |
| **Doing** | `doing/` | Tarefa em execução ativa |
| **Paused** | `paused/` | Tarefa pausada por prioridade, bloqueio ou outro motivo |
| **Done** | `done/` | Tarefa concluída e validada |

---

## 📝 Convenção de Nomes

### Planos

Planos na pasta `plans/` devem seguir a convenção:

```
NNN-sequencia-titulo-MMM.md
```

Onde:
- `NNN` = Número de prioridade / ordem de execução (ex: 001, 002, 003...)
- `sequencia` = Número sequencial dentro do tema (ex: 01, 02, 03...)
- `titulo` = Descrição breve da tarefa (kebab-case, máximo 5 palavras)
- `MMM` = Versão ou identificador único (ex: 001, v2, rev1)

**Exemplos:**
- `001-01-setup-banco-dados-001.md` - Setup do banco de dados
- `002-01-implementar-autenticacao-001.md` - Implementar sistema de autenticação
- `002-02-adicionar-oauth-manus-001.md` - Adicionar integração OAuth
- `003-01-criar-dashboard-admin-001.md` - Dashboard administrativo

### Tarefas

Tarefas derivadas de planos herdam a estrutura, mas com sufixo de status:

```
doing/NNN-sequencia-titulo-MMM.md
done/NNN-sequencia-titulo-MMM.md
paused/NNN-sequencia-titulo-MMM.md
```

**Exemplo de progressão:**
```
plans/001-01-setup-banco-dados-001.md → doing/001-01-setup-banco-dados-001.md → done/001-01-setup-banco-dados-001.md
```

---

## 🔄 Workflow de Tarefas

### Iniciando uma Tarefa

Quando você vai começar a trabalhar em uma tarefa:

1. **Localize o arquivo** em `plans/`
2. **Mova para `doing/`** com o mesmo nome
3. **Adicione um cabeçalho** no arquivo indicando início:

```markdown
# Tarefa: Setup do Banco de Dados

**Status:** 🟡 Em Andamento
**Iniciado em:** 2026-07-07 às 14:30
**Responsável:** Tiago França

---

## Contexto
[Conteúdo da tarefa...]
```

### Durante a Execução

- Mantenha o arquivo **atualizado** com progresso e bloqueios
- Adicione **notas diárias** ou marcos importantes
- Registre **decisões técnicas** e **trade-offs** considerados

### Pausando uma Tarefa

Se precisar pausar por qualquer motivo (prioridade menor, bloqueio, etc):

1. **Mova o arquivo** de `doing/` para `paused/`
2. **Atualize o cabeçalho:**

```markdown
**Status:** ⏸️ Pausado
**Pausado em:** 2026-07-07 às 16:45
**Motivo:** Aguardando feedback da equipe de design
**Próximos passos:** Retomar assim que feedback chegar
```

3. **Registre o motivo** para que você ou outro dev possa retomar depois

### Retomando uma Tarefa Pausada

1. **Mova de volta** para `doing/`
2. **Atualize o cabeçalho** com data/hora de retomada:

```markdown
**Status:** 🟡 Em Andamento (Retomado)
**Retomado em:** 2026-07-08 às 09:00
**Duração total já acumulada:** 4h 15m
```

### Concluindo uma Tarefa

Quando a tarefa estiver completa:

1. **Mova o arquivo** de `doing/` para `done/`
2. **Atualize o cabeçalho** com conclusão:

```markdown
**Status:** ✅ Concluído
**Concluído em:** 2026-07-07 às 18:00
**Tempo total:** 3h 30m
**Resultado:** Banco de dados criado, migrations executadas, seed completo
```

3. **Adicione um resumo final** com:
   - O que foi feito
   - Decisões técnicas tomadas
   - Links para PRs ou commits relacionados
   - Próximas tarefas dependentes

---

## 🎯 Planos e Tarefas

### O Que É Um Plano?

Um **plano** é um documento que descreve:
- **Objetivo** principal
- **Escopo** de trabalho
- **Lista de tarefas** (checklist)
- **Dependências** com outras tarefas
- **Critérios de sucesso**

**Estrutura de um plano:**

```markdown
# Plano: Implementar Sistema de Aprovação de Cartões

**Prioridade:** Alta
**Data de Início Planejada:** 2026-07-10
**Data de Conclusão Planejada:** 2026-07-24
**Responsável:** Tiago França

## Objetivo
Implementar um workflow completo de aprovação de cartões...

## Escopo
- [x] Task 1: Criar modelo de dados
- [ ] Task 2: Implementar API de aprovação
- [ ] Task 3: Criar UI de gerenciamento
- [ ] Task 4: Testes e QA

## Critérios de Sucesso
- ✅ 100% dos testes passando
- ✅ Workflow completo funcional
- ✅ Documentação atualizada
```

### Relação Plano → Tarefas

Um plano contém múltiplas tarefas com flags de conclusão:

```
📄 plans/002-01-aprovacao-cartoes-001.md (Plano)
├── [x] 002-01-criar-modelo-dados-001.md (Concluída)
├── [x] 002-02-implementar-api-aprovacao-001.md (Concluída)
├── [ ] 002-03-criar-ui-gerenciamento-001.md (Em andamento)
└── [ ] 002-04-testes-qa-001.md (Planejada)
```

Cada tarefa listada no plano pode ter seu próprio arquivo de acompanhamento em `doing/`, `done/` ou `paused/`.

---

## 💾 Conteúdo Mínimo de Uma Tarefa

Toda tarefa deve conter:

```markdown
# Tarefa: [Título Descritivo]

**Plano Relacionado:** [Link ou nome do plano]
**Status:** 🟡 Em Andamento
**Iniciado em:** YYYY-MM-DD HH:MM
**Responsável:** Seu Nome

## Contexto
Breve explicação do que precisa ser feito e por quê.

## Escopo de Trabalho
- [ ] Subtarefa 1
- [ ] Subtarefa 2
- [ ] Subtarefa 3

## Critérios de Aceitação
- Código segue UNIVERSAL-CODE-STYLE-RULES.md (se houver na raiz do projeto ou da aplicação, use-o, se não, use o que está no mesmo nível desse arquivo - se existir)
- Todos os testes passam (`pnpm test`)
- TypeScript compila sem erros (`pnpm check`)
- Documentação atualizada

## Notas Técnicas
[Qualquer informação técnica relevante]

## Progresso
- 2026-07-07 14:30 - Iniciado
- 2026-07-07 15:30 - [Progresso ou bloqueio]

## Resultado Final
[Preenchido ao concluir]
```

---

## 🚫 O Que NÃO Fazer

❌ **Não deixe tarefas em `doing/` sem atualização** - Mova para `paused/` se não estiver ativa
❌ **Não ignore os requisitos de leitura** - Leia AGENTS.md e CLAUDE.md antes de começar
❌ **Não quebre o padrão de nomenclatura** - Use a convenção `NNN-sequencia-titulo-MMM.md`
❌ **Não cometa tarefas incompletas** - Valide contra os critérios de sucesso
❌ **Não ignore testes** - Certifique-se que `pnpm test` passa antes de mover para `done/`

---

## ✅ Checklist de Conclusão

Antes de mover uma tarefa para `done/`, verifique:

- [ ] Código segue **UNIVERSAL-CODE-STYLE-RULES.md**
- [ ] **Todos os testes passam** (`pnpm test`)
- [ ] **TypeScript compila** sem erros (`pnpm check`)
- [ ] **Code review realizado** (se aplicável)
- [ ] **Documentação atualizada** (README, ARCHITECTURE, etc)
- [ ] **Commits bem descritos** com mensagens claras
- [ ] **PR criada e aprovada** (se aplicável)
- [ ] **Nenhum `TODO` ou `FIXME`** deixado no código

---

## 📚 Referência Rápida

| Ação | Comando Útil |
|------|--------------|
| Listar tarefas em andamento | `ls -la doing/` |
| Listar tarefas concluídas | `ls -la done/` |
| Listar tarefas pausadas | `ls -la paused/` |
| Ver planos disponíveis | `ls -la plans/` |
| Rodar testes | `pnpm test` |
| Verificar tipos | `pnpm check` |
| Formatar código | `pnpm format` |
| Formatar código com prettier | `npx -y prettier --config ./.prettierrc --ignore-path .prettierignore --write .` |
| Se tiver aplicação PHP (principalmente Laravel) dentro da aplicação formatar | `pint --config ./pint.json . ` |

---

## 🔗 Recursos Adicionais

- [CLAUDE.md](../CLAUDE.md) - Instruções e convenções do projeto
- [AGENTS.md](../AGENTS.md) - Diretrizes para agentes de IA
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Arquitetura do sistema
- [UNIVERSAL-CODE-STYLE-RULES.md](../UNIVERSAL-CODE-STYLE-RULES.md) - Regras obrigatórias de código
- [README.md](../README.md) - Visão geral do projeto

---

**Última atualização:** 2026-07-07
**Responsável:** Tiago França
