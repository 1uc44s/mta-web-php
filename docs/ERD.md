# ERD — Varlık İlişki Diyagramları

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [DATABASE](DATABASE.md) (kolon ayrıntıları, tipler ve cascade kuralları orada)

Diyagramlar okunabilirlik için **alanlara bölünmüştür**. Her diyagramda yalnızca anahtar ve ilişkiyi belirleyen kolonlar gösterilir. Tam kolon listesi [DATABASE.md](DATABASE.md) içindedir. Kardinalite gösterimi: `||` tam bir, `o|` sıfır veya bir, `|{` bir veya çok, `o{` sıfır veya çok.

## 0. Genel bakış (alanlar arası)

```mermaid
flowchart LR
    subgraph IAM[Kimlik ve Erişim]
        users
        roles
        branches
    end
    subgraph MTA[MTA Köprüsü]
        mta_servers
        mta_accounts
        characters
        game_commands
    end
    subgraph ECO[Ekonomi]
        wallets
        wallet_transactions
        payment_orders
    end
    subgraph SUP[Destek ve Başvuru]
        support_tickets
        applications
    end
    subgraph CMS[İçerik]
        pages
        news_posts
        factions
        academy_courses
        message_templates
    end
    subgraph MOD[Moderasyon ve Denetim]
        sanctions
        audit_logs
    end

    users -->|game_account_links| mta_accounts
    mta_accounts --> characters
    users --> wallets --> wallet_transactions
    users --> payment_orders
    users --> support_tickets
    users --> applications
    users --> sanctions
    branches --> support_tickets
    branches --> applications
    factions --> branches
    sanctions --> game_commands
    mta_servers --> mta_accounts
    users -.-> audit_logs
```

## 1. Kimlik, rol ve branş

```mermaid
erDiagram
    users ||--o{ user_roles : "has"
    roles ||--o{ user_roles : "assigned via"
    roles ||--o{ role_permissions : "grants"
    permissions ||--o{ role_permissions : "granted by"
    users ||--o{ branch_members : "member of"
    branches ||--o{ branch_members : "has members"
    branches o|--o{ branches : "parent (escalation)"
    factions o|--o{ branches : "faction branch"
    users ||--o{ user_security_events : "logs"
    users ||--o{ email_change_requests : "requests"
    users ||--o{ consents : "gives"
    users ||--o{ user_notification_preferences : "sets"
    users ||--o{ user_contact_channels : "owns"
    users ||--o{ sessions : "has (no FK)"

    users {
        bigint id PK
        char ulid UK
        varchar username UK
        varchar email UK
        datetime email_verified_at
        varchar password "argon2id"
        varchar status
        datetime two_factor_confirmed_at
    }
    roles {
        bigint id PK
        varchar slug UK
        smallint priority
        bool is_staff
        bool is_system
    }
    permissions {
        bigint id PK
        varchar key UK
        varchar group
        bool is_sensitive
    }
    role_permissions {
        bigint role_id PK,FK
        bigint permission_id PK,FK
    }
    user_roles {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        bigint assigned_by FK
        datetime expires_at
    }
    branches {
        bigint id PK
        varchar slug UK
        varchar type
        bigint faction_id FK
        bigint parent_id FK
    }
    branch_members {
        bigint id PK
        bigint branch_id FK
        bigint user_id FK
        bool is_lead
        datetime expires_at
    }
    user_security_events {
        bigint id PK
        bigint user_id FK
        varchar event
        char ip_hash
    }
    consents {
        bigint id PK
        bigint user_id FK
        varchar type
        varchar version
        bool granted
    }
    email_change_requests {
        bigint id PK
        bigint user_id FK
        char token_hash UK
    }
    user_notification_preferences {
        bigint user_id PK,FK
        varchar category PK
        varchar channel PK
    }
    user_contact_channels {
        bigint id PK
        bigint user_id FK
        varchar channel
        char address_hash
    }
    sessions {
        varchar id PK
        bigint user_id
        int last_activity
    }
    factions {
        bigint id PK
        varchar slug UK
    }
```

## 2. MTA hesapları, karakterler ve köprü

```mermaid
erDiagram
    mta_servers ||--o{ integration_credentials : "authenticates"
    integration_credentials ||--o{ bridge_nonces : "used"
    mta_servers ||--o{ server_heartbeats : "reports"
    mta_servers ||--o{ server_stats_daily : "aggregates"
    mta_servers ||--o{ mta_accounts : "hosts"
    mta_servers ||--o{ characters : "hosts"
    mta_servers ||--o{ game_events : "emits"
    mta_servers ||--o{ game_commands : "receives"
    users ||--o{ game_account_links : "links"
    mta_accounts ||--o{ game_account_links : "linked by (max 1 active)"
    users ||--o{ game_link_codes : "generates"
    mta_accounts o|--o{ game_link_codes : "consumed by"
    mta_accounts ||--o{ characters : "owns"
    mta_accounts ||--o{ mta_account_devices : "uses"
    mta_accounts o|--o{ game_events : "subject of"
    mta_accounts o|--o{ game_commands : "target of"
    factions o|--o{ characters : "member"
    game_jobs o|--o{ characters : "works as"

    mta_servers {
        bigint id PK
        varchar slug UK
        varchar connect_host
        smallint connect_port
        datetime last_heartbeat_at
        smallint last_player_count
        bool is_maintenance
    }
    integration_credentials {
        bigint id PK
        bigint mta_server_id FK
        varchar key_id UK
        text secret "encrypted"
        datetime revoked_at
    }
    bridge_nonces {
        char nonce PK
        bigint credential_id FK
        datetime created_at
    }
    server_heartbeats {
        bigint id PK
        bigint mta_server_id FK
        smallint player_count
        datetime received_at
    }
    server_stats_daily {
        bigint mta_server_id PK,FK
        date date PK
        smallint peak_players
    }
    mta_accounts {
        bigint id PK
        char ulid UK
        bigint mta_server_id FK
        varchar mta_account_ref "UK with server"
        varchar account_name
    }
    game_account_links {
        bigint id PK
        bigint user_id FK
        bigint mta_account_id FK
        varchar status
        bigint active_mta_account_id UK "generated"
    }
    game_link_codes {
        bigint id PK
        bigint user_id FK
        varchar purpose
        char code_hash UK
        datetime expires_at
        datetime used_at
        bigint used_by_mta_account_id FK
    }
    characters {
        bigint id PK
        char ulid UK
        bigint mta_account_id FK
        varchar mta_character_ref "UK with server"
        varchar name
        bigint faction_id FK
        bigint game_job_id FK
    }
    mta_account_devices {
        bigint id PK
        bigint mta_account_id FK
        char serial_hmac
        varchar status
    }
    game_events {
        bigint id PK
        bigint mta_server_id FK
        char event_id "UK with server"
        varchar type
        varchar status
    }
    game_commands {
        bigint id PK
        char ulid UK
        bigint mta_server_id FK
        varchar type
        varchar idempotency_key UK
        varchar status
        bigint requested_by FK
    }
    users {
        bigint id PK
    }
    factions {
        bigint id PK
    }
    game_jobs {
        bigint id PK
    }
```

## 3. Ekonomi ve ödeme

```mermaid
erDiagram
    users ||--o{ wallets : "owns"
    wallet_types ||--o{ wallets : "typed"
    wallets ||--o{ wallet_transactions : "ledger"
    wallet_transactions o|--o| wallet_transactions : "reversal_of"
    wallet_types ||--o{ payment_packages : "sold as"
    users ||--o{ payment_orders : "places"
    wallets ||--o{ payment_orders : "credits"
    payment_packages o|--o{ payment_orders : "selected"
    payment_orders ||--o{ payment_events : "webhooks"
    payment_orders ||--o{ refunds : "refunded by"
    refunds o|--o| wallet_transactions : "debits"
    wallets ||--o{ wallet_adjustment_requests : "adjusted by"
    wallet_adjustment_requests o|--o| wallet_transactions : "applied as"

    wallet_types {
        bigint id PK
        varchar key UK
        bool is_purchasable
    }
    wallets {
        bigint id PK
        bigint user_id FK
        bigint wallet_type_id FK
        bigint balance "CHECK >= 0"
        int version
    }
    wallet_transactions {
        bigint id PK
        char ulid UK
        bigint wallet_id FK
        varchar direction
        bigint amount "CHECK > 0"
        bigint balance_after "CHECK >= 0"
        varchar reason
        bigint reversal_of_id FK,UK
        varchar idempotency_key UK
    }
    payment_packages {
        bigint id PK
        bigint wallet_type_id FK
        bigint price_minor
        char currency
    }
    payment_orders {
        bigint id PK
        char ulid UK
        varchar order_number UK
        bigint user_id FK
        bigint wallet_id FK
        varchar provider
        bigint amount_minor
        varchar status
    }
    payment_events {
        bigint id PK
        varchar provider "UK with event id"
        varchar provider_event_id
        bigint payment_order_id FK
        bool signature_valid
    }
    refunds {
        bigint id PK
        bigint payment_order_id FK
        bigint amount_minor
        bigint requested_by FK
        bigint approved_by FK
        bigint wallet_transaction_id FK
    }
    wallet_adjustment_requests {
        bigint id PK
        bigint wallet_id FK
        varchar direction
        bigint amount
        bigint requested_by FK
        bigint reviewed_by FK
        bigint wallet_transaction_id FK
    }
    users {
        bigint id PK
    }
```

## 4. Destek ve başvurular

```mermaid
erDiagram
    branches ||--o{ support_categories : "routes"
    support_categories ||--o{ support_tickets : "classifies"
    branches ||--o{ support_tickets : "queue"
    users ||--o{ support_tickets : "opens"
    users o|--o{ support_tickets : "assigned_to"
    characters o|--o{ support_tickets : "about"
    support_tickets ||--o{ support_messages : "thread"
    support_messages ||--o{ support_attachments : "has"
    support_tickets ||--o{ support_ticket_events : "history"
    support_categories o|--o{ support_macros : "suggests"
    branches o|--o{ support_macros : "scoped"

    branches ||--o{ application_types : "reviews"
    factions o|--o{ application_types : "for"
    application_types ||--o{ applications : "instances"
    users ||--o{ applications : "submits"
    characters o|--o{ applications : "as"
    applications ||--o{ application_answers : "answers"
    applications ||--o{ application_messages : "thread"
    applications ||--o{ application_status_history : "history"

    support_categories {
        bigint id PK
        varchar slug UK
        bigint branch_id FK
    }
    support_tickets {
        bigint id PK
        char ulid UK
        varchar number UK
        bigint user_id FK
        bigint category_id FK
        bigint branch_id FK
        bigint assigned_to FK
        varchar status
        varchar priority
    }
    support_messages {
        bigint id PK
        bigint ticket_id FK
        bigint author_id FK
        varchar visibility "public | internal"
    }
    support_attachments {
        bigint id PK
        char ulid UK
        bigint message_id FK
        varchar path
        char sha256
    }
    support_ticket_events {
        bigint id PK
        bigint ticket_id FK
        varchar type
    }
    support_macros {
        bigint id PK
        bigint branch_id FK
    }
    application_types {
        bigint id PK
        varchar slug UK
        varchar kind
        bigint branch_id FK
        bigint faction_id FK
        bool is_open
    }
    applications {
        bigint id PK
        char ulid UK
        bigint user_id FK
        bigint application_type_id FK
        varchar status
        varchar open_key UK "generated"
    }
    application_answers {
        bigint id PK
        bigint application_id FK
        varchar field_key
    }
    application_messages {
        bigint id PK
        bigint application_id FK
        varchar visibility
    }
    application_status_history {
        bigint id PK
        bigint application_id FK
        varchar to_status
    }
    users {
        bigint id PK
    }
    branches {
        bigint id PK
    }
    characters {
        bigint id PK
    }
    factions {
        bigint id PK
    }
```

## 5. İçerik (CMS), Akademi ve şablonlar

```mermaid
erDiagram
    pages ||--o{ page_sections : "composed of"
    pages ||--o{ page_revisions : "versions"
    media o|--o{ pages : "og image"
    news_categories ||--o{ news_posts : "groups"
    news_posts ||--o{ news_post_tag : ""
    tags ||--o{ news_post_tag : ""
    media o|--o{ news_posts : "cover"
    game_system_categories ||--o{ game_systems : "groups"
    application_types o|--o{ game_jobs : "apply via"
    application_types o|--o{ factions : "apply via"
    rule_categories o|--o{ factions : "rules"
    rule_categories ||--o{ rules : "contains"
    rules ||--o{ rule_revisions : "versions"
    users ||--o{ rule_acknowledgements : "acknowledges"
    academy_courses ||--o{ academy_topics : "has topics"
    academy_topics ||--o{ academy_topic_rule : ""
    rules ||--o{ academy_topic_rule : "taught in"
    users ||--o{ academy_progress : "completes"
    academy_topics ||--o{ academy_progress : ""
    announcements ||--o{ announcement_reads : ""
    users ||--o{ announcement_reads : ""
    navigation_items o|--o{ navigation_items : "parent"
    message_templates ||--o{ message_template_revisions : "versions"
    message_templates o|--o{ message_dispatches : "rendered as"
    users o|--o{ message_dispatches : "receives"

    pages {
        bigint id PK
        varchar slug UK
        varchar template
        varchar status
        bool is_system
    }
    page_sections {
        bigint id PK
        bigint page_id FK
        varchar type "system template key"
        json data
        smallint sort
    }
    page_revisions {
        bigint id PK
        bigint page_id FK
        json snapshot
    }
    media {
        bigint id PK
        char ulid UK
        varchar path
        json variants
        varchar alt_text
    }
    news_posts {
        bigint id PK
        varchar slug UK
        bigint news_category_id FK
        bigint cover_media_id FK
        varchar status
        datetime published_at
    }
    news_categories {
        bigint id PK
    }
    tags {
        bigint id PK
    }
    news_post_tag {
        bigint news_post_id PK,FK
        bigint tag_id PK,FK
    }
    game_system_categories {
        bigint id PK
    }
    game_systems {
        bigint id PK
        bigint game_system_category_id FK
        bool is_live_in_game
    }
    game_jobs {
        bigint id PK
        varchar class
        bigint application_type_id FK
    }
    factions {
        bigint id PK
        varchar type
        bigint application_type_id FK
        bigint rule_category_id FK
        bool applications_open
    }
    rule_categories {
        bigint id PK
        varchar prefix
    }
    rules {
        bigint id PK
        varchar code UK
        bigint rule_category_id FK
    }
    rule_revisions {
        bigint id PK
        bigint rule_id FK
    }
    rule_acknowledgements {
        bigint id PK
        bigint user_id FK
        varchar version
    }
    academy_courses {
        bigint id PK
        varchar slug UK
        bool is_required
        bool is_onboarding
    }
    academy_topics {
        bigint id PK
        bigint academy_course_id FK
        varchar slug
    }
    academy_topic_rule {
        bigint academy_topic_id PK,FK
        bigint rule_id PK,FK
    }
    academy_progress {
        bigint user_id PK,FK
        bigint academy_topic_id PK,FK
        datetime completed_at
    }
    announcements {
        bigint id PK
        varchar audience
        json channels
    }
    announcement_reads {
        bigint announcement_id PK,FK
        bigint user_id PK,FK
    }
    navigation_items {
        bigint id PK
        varchar location
        bigint parent_id FK
    }
    message_templates {
        bigint id PK
        varchar key "UK with channel+locale"
        varchar channel
        bool is_system
    }
    message_template_revisions {
        bigint id PK
        bigint message_template_id FK
    }
    message_dispatches {
        bigint id PK
        bigint message_template_id FK
        bigint user_id FK
        varchar status
    }
    users {
        bigint id PK
    }
    application_types {
        bigint id PK
    }
```

Bağımsız tablolar (ilişkisi yok veya yalnızca `users` ilişkisi var): `faqs`, `community_events`, `social_links`, `redirects`, `contact_messages`, `system_settings`.

## 6. Moderasyon ve denetim

```mermaid
erDiagram
    users o|--o{ sanctions : "sanctioned"
    mta_accounts o|--o{ sanctions : "sanctioned"
    characters o|--o{ sanctions : "sanctioned"
    rules o|--o{ sanctions : "violated"
    users ||--o{ sanctions : "issued_by"
    sanctions ||--o| sanction_appeals : "appealed (max 1)"
    sanctions o|--o{ game_commands : "enforced via (source)"
    users o|--o{ audit_logs : "actor"
    users ||--o{ admin_activity_logs : "views"

    sanctions {
        bigint id PK
        char ulid UK
        bigint user_id FK
        bigint mta_account_id FK
        bigint character_id FK
        varchar type
        varchar status
        bigint issued_by FK
        datetime ends_at
    }
    sanction_appeals {
        bigint id PK
        bigint sanction_id FK,UK
        bigint user_id FK
        bigint reviewed_by FK
        varchar status
    }
    audit_logs {
        bigint id PK
        char ulid UK
        bigint actor_id FK
        varchar action
        varchar target_type
        bigint target_id
        varchar severity
        char prev_hash
        char hash
    }
    admin_activity_logs {
        bigint id PK
        bigint user_id FK
        varchar route_name
    }
    game_commands {
        bigint id PK
        varchar source_type
        bigint source_id
    }
    users {
        bigint id PK
    }
    mta_accounts {
        bigint id PK
    }
    characters {
        bigint id PK
    }
    rules {
        bigint id PK
    }
```

## 7. Kritik bütünlük kuralları (özet)

| Kural | Mekanizma | Tablo |
|---|---|---|
| Bir oyun hesabı = en fazla 1 aktif web bağlantısı | Generated column + unique | `game_account_links.active_mta_account_id` |
| Kullanıcı × başvuru türü = en fazla 1 açık başvuru | Generated column + unique | `applications.open_key` |
| Aynı webhook iki kez işlenmez | Unique | `payment_events (provider, provider_event_id)` |
| Aynı köprü olayı iki kez işlenmez | Unique | `game_events (mta_server_id, event_id)` |
| Replay engeli | PK çakışması | `bridge_nonces.nonce` |
| Ledger değiştirilemez | Trigger + CHECK | `wallet_transactions` |
| Bir işlem en fazla bir kez ters çevrilir | Unique | `wallet_transactions.reversal_of_id` |
| Ceza başına tek itiraz | Unique | `sanction_appeals.sanction_id` |
| Audit kaydı değiştirilemez | Trigger + hash zinciri | `audit_logs` |
