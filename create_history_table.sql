--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE history (
    id integer NOT NULL,
    action_type character(1),
    table_name character varying(255),
    sql_statement text,
    customer_id integer,
    executed timestamp without time zone DEFAULT now(),
    error_text text
);


CREATE SEQUENCE history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



ALTER SEQUENCE history_id_seq OWNED BY history.id;

SELECT pg_catalog.setval('history_id_seq', 212190, true);

ALTER TABLE ONLY history ALTER COLUMN id SET DEFAULT nextval('history_id_seq'::regclass);

ALTER TABLE ONLY history
    ADD CONSTRAINT history_pkey PRIMARY KEY (id);

