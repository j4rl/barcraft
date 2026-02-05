# barcraft
Craft drinks with AI and with stuff you have at home

## Setup
1. Skapa databas (t.ex. `barcraft`).
2. Importera `db/schema.sql` via phpMyAdmin eller `mysql`.
3. Uppdatera `config.php` med dina DB-uppgifter.
4. Oppna http://localhost/barcraft/ i webblasaren.

## Admin och godkannande
- Nya anvandare registrerar sig men maste godkannas av admin.
- Forsta admin satter du sjalv i phpMyAdmin: satt `is_admin = 1` och `is_approved = 1` pa vald anvandare i tabellen `users`.
- Admin kan godkanna andra anvandare och satta admin-status i grannssnittet.

## AI-generator
- Fyll i din Gemini API-nyckel i `config.local.php` under `gemini.api_key` (se `config.local.example.php`).
- `config.local.php` ar git-ignorerad och ska inte committas.
- Standardmodell ar `gpt-4o-mini` men kan andras i `config.php` eller overridas i `config.local.php`.
- AI-generatorn kraver inloggning.
- PHPs cURL-extension maste vara aktiverad.

## Noteringar
- Appen anvander mysqli (ingen PDO).
- Ingredienser sparas i gemener for jamn matchning.
- Ingrediensformat: en per rad, valfritt ` - ` for mangd.
- Standardsprak ar engelska och kan andras per anvandare i profilen.
- Bladdra och sok ar publikt, men kylskap och skapande av drinkar kraver inloggning.
