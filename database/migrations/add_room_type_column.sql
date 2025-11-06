-- Add room_type column to rooms table

ALTER TABLE rooms
ADD COLUMN room_type VARCHAR(50) NULL AFTER area,
ADD INDEX idx_room_type (room_type);

-- Update existing rooms with default value
UPDATE rooms SET room_type = 'apartment' WHERE room_type IS NULL;
