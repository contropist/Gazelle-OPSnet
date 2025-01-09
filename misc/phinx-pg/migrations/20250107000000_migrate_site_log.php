<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MigrateSiteLog extends AbstractMigration {
    public function up(): void {
        $this->query("
            create extension if not exists citext
        ");

        $this->query("
            create table site_log (
                id_site_log int primary key generated by default as identity,
                created timestamptz not null default current_timestamp,
                note citext not null,
                note_ts tsvector generated always as (to_tsvector('simple', note)) stored
            )
        ");
        $this->query("create index sl_ts_note_idx ON site_log USING gist (note_ts)");

        $this->query("
            create table table_row_count (
                id_table_row_count int primary key generated by default as identity,
                table_name text not null,
                total bigint not null default 0
            )
        ");
        $this->query('create unique index trc_n_uidc ON table_row_count (table_name)');

        $this->query("
            create function track_row_count()
                returns trigger
                volatile
                language plpgsql
                as $$
                    begin
                        with delta as (
                            select tg_argv[0] as table_name,
                            case
                                when tg_op = 'INSERT' then  1
                                when tg_op = 'DELETE' then -1
                                else 0
                            end as change
                        )
                        merge into table_row_Count trc using delta on (delta.table_name = trc.table_name)
                            when matched then
                                update set total = total + delta.change
                            when not matched then
                                insert (table_name,       total)
                                values (delta.table_name, delta.change);
                        return null;
                    end;
                $$
        ");
        $this->query("
            create trigger track_row_count_site_log
                after insert or delete on site_log
                    for each row
                        execute function track_row_count('site_log')
        ");
    }

    public function down(): void {
        $this->query("
            drop function track_row_count() cascade
        ");
        $this->query("
            drop table site_log
        ");
        $this->query("
            drop table table_row_count
        ");
    }
}
