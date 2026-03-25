# LibraFlow — Guia de Cores

---

## ☀️ Tema Claro

### Fundo

| Amostra | Hex | Função | Seletor CSS |
|---|---|---|---|
| 🟨 `#FFFFFF` | `#FFFFFF` | Fundo da página | `body { background }` |
| 🟨 `#F5F5F0` | `#F5F5F0` | Fundo de cards | `.card { background }` |

---

### Header e Nav

| Hex | Função | Seletor CSS |
|---|---|---|
| `#FFFFFF` | Fundo do header | `header, nav { background }` |
| `#BC6C25` | Links de navegação | `nav a { color }` |

---

### Texto

| Hex | Função | Seletor CSS |
|---|---|---|
| `#283618` | Títulos | `h1, h2, h3 { color }` |
| `#606C38` | Parágrafos | `p { color }` |

---

### Inputs

| Hex | Função | Seletor CSS |
|---|---|---|
| `#FFFFFF` | Fundo do input | `input { background }` |
| `#BC6C25` | Borda do input | `input { border-color }` |
| `#283618` | Texto digitado | `input { color }` |

---

### Botões Primários

| Hex | Função | Seletor CSS |
|---|---|---|
| `#DDA15E` | Fundo do botão | `button { background }` |
| `#FEFAE0` | Texto do botão | `button { color }` |

---

### Botão de Tema ☀️

| Hex | Função | Seletor CSS |
|---|---|---|
| `#DDA15E` | Fundo do botão | `.theme-btn { background }` |
| `#606C38` | Label "Claro" | `#themeLabel { color }` |

---

### CSS completo — Tema Claro

```css
body {
  background: #FFFFFF;
  color: #283618;
  transition: background 0.4s ease, color 0.4s ease;
}

header, nav {
  background: #FFFFFF;
  transition: background 0.4s ease;
}

nav a {
  color: #BC6C25;
}

h1, h2, h3 {
  color: #283618;
}

p {
  color: #606C38;
}

input, textarea {
  background: #FFFFFF;
  border-color: #BC6C25;
  color: #283618;
}

.card {
  background: #F5F5F0;
  border: 0.5px solid #BC6C25;
  transition: background 0.4s ease, border-color 0.4s ease;
}

button {
  background: #DDA15E;
  color: #FEFAE0;
  transition: background 0.4s ease;
}

.theme-btn {
  background: #DDA15E;
}

#themeLabel {
  color: #606C38;
}
```

---
---

## 🌙 Tema Escuro

### Hierarquia de profundidade

> Do mais escuro ao mais claro, cada camada da interface tem um tom diferente para criar profundidade visual.

| Nível | Hex | Onde usar |
|---|---|---|
| 1 — Mais fundo | `#1C2410` | Fundo da página |
| 2 — Superfície | `#243015` | Header, nav, cards |
| 3 — Entrada | `#2A3318` | Fundo de inputs |
| 4 — Borda | `#3A4E1E` | Bordas e separadores |
| 5 — Ação | `#4A6020` | Botões primários |
| 6 — Texto secundário | `#A8C97F` | Parágrafos e links |
| 7 — Texto principal | `#D4E8B0` | Títulos |

---

### Fundo

| Hex | Função | Seletor CSS |
|---|---|---|
| `#1C2410` | Fundo da página | `body.dark { background }` |
| `#243015` | Fundo de cards | `body.dark .card { background }` |

---

### Header e Nav

| Hex | Função | Seletor CSS |
|---|---|---|
| `#243015` | Fundo do header | `body.dark header, nav { background }` |
| `#3A4E1E` | Borda inferior do header | `body.dark header { border-bottom-color }` |
| `#A8C97F` | Links de navegação | `body.dark nav a { color }` |

---

### Texto

| Hex | Função | Seletor CSS |
|---|---|---|
| `#D4E8B0` | Títulos | `body.dark h1, h2, h3 { color }` |
| `#A8C97F` | Parágrafos | `body.dark p { color }` |

---

### Inputs

| Hex | Função | Seletor CSS |
|---|---|---|
| `#2A3318` | Fundo do input | `body.dark input { background }` |
| `#3A4E1E` | Borda do input | `body.dark input { border-color }` |
| `#A8C97F` | Texto digitado | `body.dark input { color }` |

---

### Botões Primários

| Hex | Função | Seletor CSS |
|---|---|---|
| `#4A6020` | Fundo do botão | `body.dark button { background }` |
| `#D4E8B0` | Texto do botão | `body.dark button { color }` |

---

### Botão de Tema 🌙

| Hex | Função | Seletor CSS |
|---|---|---|
| `#3A4E1E` | Fundo do botão | `body.dark .theme-btn { background }` |
| `#A8C97F` | Label "Escuro" | `body.dark #themeLabel { color }` |

---

### CSS completo — Tema Escuro

```css
body.dark {
  background: #1C2410;
  color: #D4E8B0;
}

body.dark header,
body.dark nav {
  background: #243015;
  border-bottom-color: #3A4E1E;
}

body.dark nav a {
  color: #A8C97F;
}

body.dark h1,
body.dark h2,
body.dark h3 {
  color: #D4E8B0;
}

body.dark p {
  color: #A8C97F;
}

body.dark input,
body.dark textarea {
  background: #2A3318;
  border-color: #3A4E1E;
  color: #A8C97F;
}

body.dark .card {
  background: #243015;
  border-color: #3A4E1E;
}

body.dark button {
  background: #4A6020;
  color: #D4E8B0;
}

body.dark .theme-btn {
  background: #3A4E1E;
}

body.dark #themeLabel {
  color: #A8C97F;
}
```

---

## Resumo rápido

| Elemento | Claro | Escuro |
|---|---|---|
| Fundo da página | `#FFFFFF` | `#1C2410` |
| Fundo de cards | `#F5F5F0` | `#243015` |
| Header / nav | `#FFFFFF` | `#243015` |
| Borda inferior header | — | `#3A4E1E` |
| Links de nav | `#BC6C25` | `#A8C97F` |
| Títulos | `#283618` | `#D4E8B0` |
| Parágrafos | `#606C38` | `#A8C97F` |
| Fundo de input | `#FFFFFF` | `#2A3318` |
| Borda de input | `#BC6C25` | `#3A4E1E` |
| Texto do input | `#283618` | `#A8C97F` |
| Fundo do botão | `#DDA15E` | `#4A6020` |
| Texto do botão | `#FEFAE0` | `#D4E8B0` |
| Botão de tema | `#DDA15E` | `#3A4E1E` |
| Label do tema | `#606C38` | `#A8C97F` |
