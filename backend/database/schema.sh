-- =========================================================
-- 0) Extensions & helpers
-- =========================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;  -- for gen_random_uuid()

-- Tiny helper domain for positive small ints (feet, counts, etc.)
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'pos_smallint') THEN
    CREATE DOMAIN pos_smallint AS INT CHECK (VALUE >= 0 AND VALUE <= 32767);
  END IF;
END$$;

-- =========================================================
-- 1) Users (PC owners / DMs)
-- =========================================================
CREATE TABLE users (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email             TEXT UNIQUE NOT NULL,
  display_name      TEXT NOT NULL,
  password_hash     TEXT,                    -- if you handle auth here
  created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =========================================================
-- 2) Taxonomy: groups/folders (factions, locations, custom)
-- Tree with arbitrary nesting; characters can be in many.
-- =========================================================
CREATE TABLE groups (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name              TEXT NOT NULL,
  kind              TEXT NOT NULL CHECK (kind IN ('faction','location','custom')),
  parent_id         UUID REFERENCES groups(id) ON DELETE CASCADE,
  owner_user_id     UUID REFERENCES users(id) ON DELETE SET NULL,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE(parent_id, name)                    -- no dup siblings
);

-- =========================================================
-- 3) Core lookups (skills, languages, conditions, damage, tools, armor, weapons…)
-- These are seedable with SRD lists; kept flexible for homebrew.
-- =========================================================
CREATE TABLE abilities (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'STR','DEX','CON','INT','WIS','CHA'
  name TEXT NOT NULL
);

CREATE TABLE skills (
  id           SMALLSERIAL PRIMARY KEY,
  key          TEXT UNIQUE NOT NULL,         -- 'acrobatics','arcana',...
  name         TEXT NOT NULL,
  ability_key  TEXT NOT NULL REFERENCES abilities(key) ON UPDATE CASCADE
);

CREATE TABLE languages (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'common','elvish',...
  name TEXT NOT NULL
);

CREATE TABLE conditions (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'blinded','charmed','exhaustion',...
  name TEXT NOT NULL
);

CREATE TABLE damage_types (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'slashing','fire','necrotic',...
  name TEXT NOT NULL
);

CREATE TABLE tools (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'thieves_tools','alchemists_supplies',...
  name TEXT NOT NULL
);

CREATE TABLE armor_types (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'light','medium','heavy','shield'
  name TEXT NOT NULL
);

CREATE TABLE weapon_categories (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,                 -- 'simple','martial' (extend as needed)
  name TEXT NOT NULL
);

-- =========================================================
-- 4) Races/Species & Subraces (5e 2014 compatible; extensible)
-- =========================================================
CREATE TABLE species (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          TEXT UNIQUE NOT NULL,        -- 'Elf','Human','Orc','Dragonborn',...
  size          TEXT NOT NULL CHECK (size IN ('Tiny','Small','Medium','Large','Huge','Gargantuan')),
  base_speed_ft pos_smallint NOT NULL DEFAULT 30,
  default_darkvision_ft pos_smallint DEFAULT 0,
  srd_key       TEXT UNIQUE,                 -- optional mapping to SRD
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE subraces (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  species_id    UUID NOT NULL REFERENCES species(id) ON DELETE CASCADE,
  name          TEXT NOT NULL,
  UNIQUE(species_id, name)
);

-- =========================================================
-- 5) Classes & Subclasses (support multiclass)
-- =========================================================
CREATE TABLE classes (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          TEXT UNIQUE NOT NULL,        -- 'Fighter','Wizard','Bard',...
  hit_die_size  SMALLINT NOT NULL CHECK (hit_die_size IN (6,8,10,12)),
  primary_ability_key TEXT REFERENCES abilities(key),
  srd_key       TEXT UNIQUE
);

CREATE TABLE subclasses (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  class_id      UUID NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  name          TEXT NOT NULL,
  srd_key       TEXT,
  UNIQUE(class_id, name)
);

-- =========================================================
-- 6) Backgrounds & Feats & Features
-- =========================================================
CREATE TABLE backgrounds (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name       TEXT UNIQUE NOT NULL,
  feature    TEXT,                            -- e.g., 'Criminal Contact'
  srd_key    TEXT UNIQUE
);

CREATE TABLE feats (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name        TEXT UNIQUE NOT NULL,
  prereq_text TEXT
);

CREATE TABLE features (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name        TEXT NOT NULL,                  -- racial traits, class features
  source      TEXT NOT NULL CHECK (source IN ('race','subrace','class','subclass','background','other')),
  source_id   UUID,                           -- optional pointer to above
  rules_text  TEXT
);

-- =========================================================
-- 7) Characters (PCs and NPCs unified)
-- =========================================================
CREATE TABLE characters (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  is_pc                 BOOLEAN NOT NULL DEFAULT FALSE,
  owner_user_id         UUID REFERENCES users(id) ON DELETE SET NULL, -- who controls this PC/NPC (optional)
  name                  TEXT NOT NULL,
  portrait_url          TEXT,
  alignment             TEXT CHECK (alignment IN (
                         'LG','NG','CG','LN','N','CN','LE','NE','CE'
                       )),
  species_id            UUID REFERENCES species(id) ON DELETE SET NULL,
  subrace_id            UUID REFERENCES subraces(id) ON DELETE SET NULL,
  background_id         UUID REFERENCES backgrounds(id) ON DELETE SET NULL,

  -- identity
  gender                TEXT,
  age_years             pos_smallint,
  size                  TEXT CHECK (size IN ('Tiny','Small','Medium','Large','Huge','Gargantuan')),

  -- leveling
  level                 pos_smallint NOT NULL DEFAULT 1 CHECK (level BETWEEN 1 AND 20),
  experience_points     INT NOT NULL DEFAULT 0,
  proficiency_bonus     SMALLINT NOT NULL DEFAULT 2,   -- can be derived; denormalized for speed

  -- ability scores
  str                   SMALLINT NOT NULL DEFAULT 10 CHECK (str BETWEEN 1 AND 30),
  dex                   SMALLINT NOT NULL DEFAULT 10 CHECK (dex BETWEEN 1 AND 30),
  con                   SMALLINT NOT NULL DEFAULT 10 CHECK (con BETWEEN 1 AND 30),
  int                   SMALLINT NOT NULL DEFAULT 10 CHECK (int BETWEEN 1 AND 30),
  wis                   SMALLINT NOT NULL DEFAULT 10 CHECK (wis BETWEEN 1 AND 30),
  cha                   SMALLINT NOT NULL DEFAULT 10 CHECK (cha BETWEEN 1 AND 30),

  -- saves (proficiency flags)
  save_prof_str         BOOLEAN NOT NULL DEFAULT FALSE,
  save_prof_dex         BOOLEAN NOT NULL DEFAULT FALSE,
  save_prof_con         BOOLEAN NOT NULL DEFAULT FALSE,
  save_prof_int         BOOLEAN NOT NULL DEFAULT FALSE,
  save_prof_wis         BOOLEAN NOT NULL DEFAULT FALSE,
  save_prof_cha         BOOLEAN NOT NULL DEFAULT FALSE,

  -- combat core
  armor_class           SMALLINT NOT NULL DEFAULT 10,
  initiative_misc       SMALLINT NOT NULL DEFAULT 0,   -- add to Dex mod
  speed_walk_ft         pos_smallint NOT NULL DEFAULT 30,
  speed_fly_ft          pos_smallint DEFAULT 0,
  speed_swim_ft         pos_smallint DEFAULT 0,
  speed_climb_ft        pos_smallint DEFAULT 0,
  speed_burrow_ft       pos_smallint DEFAULT 0,

  -- senses (passive Perception is derived; store for convenience)
  passive_perception    SMALLINT DEFAULT 10,
  darkvision_ft         pos_smallint DEFAULT 0,
  blindsight_ft         pos_smallint DEFAULT 0,
  tremorsense_ft        pos_smallint DEFAULT 0,
  truesight_ft          pos_smallint DEFAULT 0,

  -- HP tracking
  hit_point_max         pos_smallint NOT NULL DEFAULT 1,
  hit_points_current    INT NOT NULL DEFAULT 1,
  temp_hp               pos_smallint DEFAULT 0,

  -- death saves & status
  death_saves_success   SMALLINT NOT NULL DEFAULT 0 CHECK (death_saves_success BETWEEN 0 AND 3),
  death_saves_failure   SMALLINT NOT NULL DEFAULT 0 CHECK (death_saves_failure BETWEEN 0 AND 3),
  exhaustion_level      SMALLINT NOT NULL DEFAULT 0 CHECK (exhaustion_level BETWEEN 0 AND 6),
  inspiration           BOOLEAN NOT NULL DEFAULT FALSE,

  -- roleplay
  bonds                 TEXT,
  ideals                TEXT,
  flaws                 TEXT,
  personality_traits    TEXT,
  mbti_type             TEXT CHECK (mbti_type ~ '^[E|I][S|N][T|F][J|P]$'),

  -- NPC statblock assist (optional)
  challenge_rating      NUMERIC(4,2),                 -- e.g., 0.25, 5.00
  statblock_json        JSONB,                        -- for monsters/NPC blocks

  -- audit
  last_interacted_at    TIMESTAMPTZ,
  created_at            TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at            TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- characters <-> groups (folders, factions, locations)
CREATE TABLE character_groups (
  character_id UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  group_id     UUID NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
  PRIMARY KEY (character_id, group_id)
);

-- =========================================================
-- 8) Multiclass detail (per class level)
-- =========================================================
CREATE TABLE character_classes (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  character_id    UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  class_id        UUID NOT NULL REFERENCES classes(id) ON DELETE RESTRICT,
  subclass_id     UUID REFERENCES subclasses(id) ON DELETE SET NULL,
  level           pos_smallint NOT NULL CHECK (level BETWEEN 1 AND 20),
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE(character_id, class_id)
);

-- =========================================================
-- 9) Proficiencies & Skills
-- =========================================================
-- Skill proficiency: none / proficient / expertise (x2)
CREATE TABLE character_skills (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  skill_id      SMALLINT NOT NULL REFERENCES skills(id) ON DELETE RESTRICT,
  proficiency   TEXT NOT NULL CHECK (proficiency IN ('none','proficient','expertise')),
  misc_bonus    SMALLINT NOT NULL DEFAULT 0,     -- situational, items, etc.
  PRIMARY KEY (character_id, skill_id)
);

-- Armor proficiency
CREATE TABLE character_armor_proficiencies (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  armor_type_id SMALLINT NOT NULL REFERENCES armor_types(id) ON DELETE RESTRICT,
  PRIMARY KEY (character_id, armor_type_id)
);

-- Weapon proficiency (by category; extend to specific weapons if desired)
CREATE TABLE character_weapon_proficiencies (
  character_id       UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  weapon_category_id SMALLINT NOT NULL REFERENCES weapon_categories(id) ON DELETE RESTRICT,
  PRIMARY KEY (character_id, weapon_category_id)
);

-- Tool proficiency
CREATE TABLE character_tool_proficiencies (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  tool_id       SMALLINT NOT NULL REFERENCES tools(id) ON DELETE RESTRICT,
  proficiency   TEXT NOT NULL CHECK (proficiency IN ('proficient','expertise')),
  PRIMARY KEY (character_id, tool_id)
);

-- Languages known
CREATE TABLE character_languages (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  language_id   SMALLINT NOT NULL REFERENCES languages(id) ON DELETE RESTRICT,
  PRIMARY KEY (character_id, language_id)
);

-- Resistances / immunities / vulnerabilities
CREATE TABLE character_damage_modifiers (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  damage_type_id SMALLINT NOT NULL REFERENCES damage_types(id) ON DELETE RESTRICT,
  kind          TEXT NOT NULL CHECK (kind IN ('resistance','immunity','vulnerability')),
  PRIMARY KEY (character_id, damage_type_id, kind)
);

CREATE TABLE character_condition_immunities (
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  condition_id  SMALLINT NOT NULL REFERENCES conditions(id) ON DELETE RESTRICT,
  PRIMARY KEY (character_id, condition_id)
);

-- =========================================================
-- 10) Inventory / Equipment
-- =========================================================
CREATE TABLE items (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          TEXT NOT NULL,
  category      TEXT,                      -- 'weapon','armor','gear','consumable','magic','tool'
  weight_lb     NUMERIC(6,2),
  value_gp      NUMERIC(10,2),
  properties    JSONB,                     -- e.g., { "versatile":"1d10", "finesse": true }
  rules_text    TEXT
);

CREATE TABLE character_items (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  character_id   UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  item_id        UUID REFERENCES items(id) ON DELETE SET NULL, -- can be ad-hoc:
  name_override  TEXT,                                        -- if null, use items.name
  qty            pos_smallint NOT NULL DEFAULT 1,
  attuned        BOOLEAN NOT NULL DEFAULT FALSE,
  equipped       BOOLEAN NOT NULL DEFAULT FALSE,
  slot_hint      TEXT,               -- 'main_hand','off_hand','armor','ring','wondrous', etc.
  notes          TEXT
);

-- Quick armor & weapon stats (optional convenience tables)
CREATE TABLE armor (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  item_id    UUID UNIQUE REFERENCES items(id) ON DELETE CASCADE,
  armor_type_id SMALLINT NOT NULL REFERENCES armor_types(id) ON DELETE RESTRICT,
  base_ac    SMALLINT NOT NULL,           -- rules vary by armor type
  max_dex    SMALLINT,                    -- medium armor cap, etc.
  stealth_disadvantage BOOLEAN NOT NULL DEFAULT FALSE,
  strength_req SMALLINT
);

CREATE TABLE weapons (
  id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  item_id    UUID UNIQUE REFERENCES items(id) ON DELETE CASCADE,
  category_id SMALLINT NOT NULL REFERENCES weapon_categories(id) ON DELETE RESTRICT,
  damage_dice TEXT NOT NULL,              -- '1d8'
  damage_type_id SMALLINT NOT NULL REFERENCES damage_types(id) ON DELETE RESTRICT,
  properties JSONB                        -- e.g., { "finesse": true, "heavy": false, ...}
);

-- =========================================================
-- 11) Features, Feats, Custom Per-Character features
-- =========================================================
CREATE TABLE character_features (
  character_id UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  feature_id   UUID NOT NULL REFERENCES features(id) ON DELETE RESTRICT,
  acquired_at_level pos_smallint,
  PRIMARY KEY (character_id, feature_id)
);

CREATE TABLE character_feats (
  character_id UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  feat_id      UUID NOT NULL REFERENCES feats(id) ON DELETE RESTRICT,
  acquired_at_level pos_smallint,
  PRIMARY KEY (character_id, feat_id)
);

-- Generic per-character resource pools (ki, sorcery points, rage, etc.)
CREATE TABLE character_resources (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  key           TEXT NOT NULL,              -- 'ki','sorcery_points','rage','superiority_dice', etc.
  max_value     pos_smallint NOT NULL,
  current_value pos_smallint NOT NULL,
  notes         TEXT,
  UNIQUE(character_id, key)
);

-- =========================================================
-- 12) Spellcasting (SRD spells + per character)
-- =========================================================
CREATE TABLE spells (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name           TEXT UNIQUE NOT NULL,
  level          SMALLINT NOT NULL CHECK (level BETWEEN 0 AND 9), -- 0 = cantrip
  school         TEXT,                                            -- conjuration, evocation...
  casting_time   TEXT NOT NULL,
  range_text     TEXT NOT NULL,                                   -- e.g., '60 feet'
  components     JSONB,                                           -- { "v":true,"s":true,"m":"a pearl worth 100gp" }
  duration_text  TEXT NOT NULL,
  concentration  BOOLEAN NOT NULL DEFAULT FALSE,
  ritual         BOOLEAN NOT NULL DEFAULT FALSE,
  description    TEXT,
  classes        TEXT[]                                           -- ['wizard','sorcerer'] (optional helper)
);

-- Known/prepared spells per caster
CREATE TABLE character_spells (
  character_id     UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  spell_id         UUID NOT NULL REFERENCES spells(id) ON DELETE RESTRICT,
  learned_from     TEXT,                       -- 'class:Wizard','feat:MagicInitiate','item:Wand of...'
  known            BOOLEAN NOT NULL DEFAULT TRUE,
  prepared         BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (character_id, spell_id)
);

-- Slots per level (supports long-rest refresh vs pact magic if you add a flag)
CREATE TABLE character_spell_slots (
  character_id UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  slot_level   SMALLINT NOT NULL CHECK (slot_level BETWEEN 1 AND 9),
  total        pos_smallint NOT NULL DEFAULT 0,
  expended     pos_smallint NOT NULL DEFAULT 0,
  is_pact_magic BOOLEAN NOT NULL DEFAULT FALSE,  -- Warlock
  PRIMARY KEY (character_id, slot_level, is_pact_magic)
);

-- Spellcasting ability & DC/attack helpers (denormalized for speed)
CREATE TABLE character_spellcasting_profiles (
  id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  character_id       UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  source             TEXT NOT NULL,                -- 'Wizard','Cleric','Warlock Pact','Feat:Magic Initiate'
  ability_key        TEXT NOT NULL REFERENCES abilities(key) ON UPDATE CASCADE,
  spell_save_dc      SMALLINT NOT NULL,            -- 8 + prof + ability mod (+ misc)
  spell_attack_bonus SMALLINT NOT NULL             -- prof + ability mod (+ misc)
);

-- =========================================================
-- 13) Conditions on characters (active effects)
-- =========================================================
CREATE TABLE character_conditions (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  condition_id  SMALLINT NOT NULL REFERENCES conditions(id) ON DELETE RESTRICT,
  started_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  ends_at       TIMESTAMPTZ,
  notes         TEXT
);

-- =========================================================
-- 14) Relationships (directed edges)
-- =========================================================
CREATE TABLE relationship_types (
  id   SMALLSERIAL PRIMARY KEY,
  key  TEXT UNIQUE NOT NULL,   -- 'friend','rival','parent','sibling','mentor','ally','enemy','spouse','employer','member_of','leader_of','adopted_parent','adopted_child','romantic','acquaintance'
  name TEXT NOT NULL
);

CREATE TABLE relationships (
  id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  from_character_id  UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  to_character_id    UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  type_id            SMALLINT NOT NULL REFERENCES relationship_types(id) ON DELETE RESTRICT,
  is_biological      BOOLEAN NOT NULL DEFAULT FALSE,   -- true for biological parent/child/etc.
  knowledge          NUMERIC(3,2) NOT NULL DEFAULT 0.50 CHECK (knowledge >= 0 AND knowledge <= 1),  -- how much detail one knows about the other
  trust              NUMERIC(4,3) NOT NULL DEFAULT 0.000 CHECK (trust >= -1 AND trust <= 1),        -- -1..1 dislike→like
  affection          NUMERIC(4,3) NOT NULL DEFAULT 0.000 CHECK (affection >= -1 AND affection <= 1),-- -1..1 hatred→love
  familiarity        TEXT NOT NULL DEFAULT 'acquaintance' CHECK (familiarity IN ('none','acquaintance','familiar','intimate')),
  notes              TEXT,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT no_self_relation CHECK (from_character_id <> to_character_id)
);

CREATE INDEX idx_relationships_from ON relationships(from_character_id);
CREATE INDEX idx_relationships_to   ON relationships(to_character_id);

-- =========================================================
-- 15) Campaigns / Sessions / Memory (optional but handy)
-- =========================================================
CREATE TABLE campaigns (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name        TEXT NOT NULL,
  owner_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE campaign_memberships (
  campaign_id  UUID NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
  character_id UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  role         TEXT CHECK (role IN ('pc','npc','dm_helper')),
  PRIMARY KEY (campaign_id, character_id)
);

CREATE TABLE sessions (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  campaign_id  UUID NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
  started_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  ended_at     TIMESTAMPTZ
);

CREATE TABLE session_logs (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  session_id    UUID REFERENCES sessions(id) ON DELETE CASCADE,
  character_id  UUID REFERENCES characters(id) ON DELETE SET NULL,
  title         TEXT,
  entry         TEXT NOT NULL,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =========================================================
-- 16) Conversations (for your chat UI + memory)
-- =========================================================
CREATE TABLE conversations (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  campaign_id     UUID REFERENCES campaigns(id) ON DELETE SET NULL,
  title           TEXT,
  created_by_user UUID REFERENCES users(id) ON DELETE SET NULL,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE conversation_participants (
  conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
  character_id    UUID NOT NULL REFERENCES characters(id) ON DELETE CASCADE,
  PRIMARY KEY (conversation_id, character_id)
);

CREATE TABLE messages (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  conversation_id   UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
  sender_character_id UUID REFERENCES characters(id) ON DELETE SET NULL,
  role              TEXT NOT NULL CHECK (role IN ('user','assistant','system','narrator')),
  content           TEXT NOT NULL,
  created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =========================================================
-- 17) Indices you’ll appreciate later
-- =========================================================
CREATE INDEX idx_characters_name_trgm ON characters USING GIN (name gin_trgm_ops);
-- ^ if pg_trgm is available (CREATE EXTENSION pg_trgm), great for fuzzy find

CREATE INDEX idx_characters_last_seen ON characters(last_interacted_at);
CREATE INDEX idx_character_items_char ON character_items(character_id);
CREATE INDEX idx_messages_convo_time  ON messages(conversation_id, created_at);
