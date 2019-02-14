create table ranges
(
  id serial
    constraint ranges_pk
      primary key,
  start bigint not null,
  "end" bigint not null,
  free bigint not null
);

create table numbers
(
  range_id int not null,
  position bigint not null,
  number bigint not null,
  constraint numbers_pk
    primary key (range_id, position)
);
comment on table numbers is 'Numbers shuffled positions';

create unique index numbers_numbers
  on numbers (range_id, number);



-------------------------------------------------
  -- Extract free number from range.
  -- Used Fisher–Yates shuffle principle to get random unique numbers.
  --
CREATE OR REPLACE FUNCTION allocate_number(rangeid INT) RETURNS BIGINT AS
$$
DECLARE
  free_count BIGINT;
  range_start BIGINT;
  number_index BIGINT;
  real_number BIGINT;
  last_number BIGINT;
BEGIN
  SELECT "free", "start" INTO free_count, range_start FROM "ranges" WHERE "id" = rangeid;
  IF free_count = 0 THEN
    RAISE EXCEPTION '0 free numbers';
  END IF;

  -- Get random position from 0 to free_count-1
  number_index = floor(random() * free_count)::BIGINT;


  -- If the chosen position is not the last, we need to move last number to chosen number place.
  IF number_index <> (free_count - 1) THEN
    -- Find the last number if exists.
    SELECT "number" INTO last_number FROM "numbers"
    WHERE "range_id" = rangeid AND "position" = free_count - 1;

    IF last_number IS NULL THEN
      last_number = free_count - 1;
    ELSE
      -- Remove last number record, it will be moved instead of our number.
      DELETE FROM "numbers"
      WHERE rangeid = rangeid AND "position" = free_count - 1;
    END IF;
  END IF;

  -- Extract number from position.
  SELECT number INTO real_number FROM "numbers" WHERE "range_id" = rangeid AND "position" = number_index;
  IF real_number IS NULL THEN
    real_number := number_index;
  ELSE
    DELETE FROM "numbers" WHERE rangeid = rangeid AND "position" = number_index;
  END IF;

  -- Move last number to chosen position if it is not from last position.
  IF number_index <> (free_count - 1) THEN
    INSERT INTO "numbers" ("range_id", "position", "number") VALUES (rangeid, number_index, last_number);
  END IF;

  -- Reduce free numbers count for range.
  UPDATE "ranges" SET "free" = free_count-1 WHERE "id" = rangeid;

  -- Our numbers count from 0. Answer must be counted from range.start.
  RETURN real_number + range_start;
END
$$ LANGUAGE plpgsql;


-------------------------------------------------
  -- Release number from range.
  --
CREATE OR REPLACE FUNCTION release_number(rangeid INT, releasenumber BIGINT) RETURNS BIGINT AS
$$
DECLARE
  free_count BIGINT;
  range_start BIGINT;
  ournumber BIGINT;
  error BOOLEAN = false;
BEGIN
  SELECT "free", "start" INTO free_count, range_start FROM "ranges" WHERE "id" = rangeid;
  ournumber := releasenumber - range_start;

  -- Check the number is busy now. On its place must be other number and it must not be on other place.
  IF (ournumber < free_count) THEN
    SELECT CASE WHEN count(*) > 0 THEN false ELSE true END INTO error FROM "numbers"
    WHERE "range_id" = rangeid AND "position" = ournumber;
  END IF;

  IF NOT error THEN
    SELECT CASE WHEN count(*) > 0 THEN true ELSE false END INTO error FROM "numbers"
    WHERE "range_id" = rangeid AND "number" = ournumber;
  END IF;

  IF error THEN
    RAISE EXCEPTION 'Cannot release free number';
  END IF;

  -- Add number to end
  INSERT INTO "numbers" ("range_id", "position", "number") VALUES (rangeid, free_count, ournumber);

  UPDATE "ranges" SET "free" = free_count+1 WHERE "id" = rangeid;

  RETURN releasenumber;
END
$$ LANGUAGE plpgsql;


