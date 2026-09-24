USE hamayesh_yar;

ALTER TABLE registrations
  ADD COLUMN organization VARCHAR(255) NULL AFTER phone,
  ADD COLUMN message VARCHAR(1000) NULL AFTER organization;

CREATE INDEX idx_reg_event_status ON registrations(event_id, status);
CREATE INDEX idx_reg_user ON registrations(user_id);
