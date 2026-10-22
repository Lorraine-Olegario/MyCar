- TEM COMO CONSUMIR UMA API DAS MARCAR DOS CARROS E COLOCAR NO SELECT, DEPOIS DE SELECIONAR A MARCA VOCE SELECIONA OS MODELOS DAQUELA MARCA


---

## Realizado

### Item 1 — Consumir API de marcas dos carros no select; após escolher a marca, selecionar o modelo
- Branch: `tarefa38/1-selects-marca-modelo-fipe`
- Commit: `12d5f63`
- O que foi feito: reaproveitada a API FIPE (Parallelum) já integrada. Novas rotas `/fipe/brands|models|years` sem depender de veículo (`routes/web.php`, `VehicleFipeController`; as rotas por veículo continuam). Em `Vehicles/Form.vue`, Categoria passou para antes de Marca; para Carro/Moto, Marca e Modelo viram selects encadeados (modelo só habilita após a marca). O nome escolhido é gravado em `brand`/`model`. Na edição, marca/modelo existentes são pré-selecionados quando o nome casa com o catálogo.
- Validação: `php artisan test` — 349 testes passando (novo teste das rotas sem veículo); `vite build` ok.
- Observações: categoria "Outro" e falha da API usam digitação manual (há link "informar manualmente"). Mudar a categoria limpa marca/modelo. Versão continua texto livre. Não testado visualmente no navegador.
