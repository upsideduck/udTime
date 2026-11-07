-- Lookup values for absence types (used by a fresh bundled database only)
INSERT INTO lookups (type, name, code, position) VALUES
  ('againstworktime', 'Holiday', 1, 1),
  ('asworktime', 'Vacation', 1, 1),
  ('asworktime', 'Sick leave', 2, 2);
