-- Step 10: enrich schedules for full timetable management
ALTER TABLE schedules
    ADD COLUMN description TEXT NULL AFTER type;

CREATE INDEX idx_schedule_event_date_time ON schedules(event_id, schedule_date, start_time, sort_order);
