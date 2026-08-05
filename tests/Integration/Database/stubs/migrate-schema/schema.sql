CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (1, '2014_10_12_000000_create_people_table', 1);
CREATE TABLE "people" ("id" integer primary key autoincrement not null, "name" varchar not null);
