<?php

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class DmGrammar extends Grammar
{
    /**
     * The keyword identifier wrapper format.
     *
     * @var string
     */
    protected $wrapper = '%s';

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Collate', 'Increment', 'Nullable', 'Default', 'VirtualAs', 'Comment'];

    /**
     * The possible column serials.
     *
     * @var array
     */
    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * @var string
     */
    protected $schema_prefix = '';

    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = true;

    /**
     * Compile a create table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  mixed  $table
     * @return string
     */
    public function wrapTable($table)
    {
        return $this->getSchemaPrefix().parent::wrapTable($table);
    }

    /**
     * Get the schema prefix.
     *
     * @return string
     */
    public function getSchemaPrefix()
    {
        return ! empty($this->schema_prefix) ? $this->schema_prefix.'.' : '';
    }

    /**
     * Set the schema prefix.
     *
     * @param  string  $prefix
     */
    public function setSchemaPrefix($prefix)
    {
        $this->schema_prefix = $prefix;
    }

    /**
     * Compile the query to determine the id of the table.
     *
     * @param  string  $schema
     * @param  string  $table
     * @return string
     */
    public function compileTableId($schema, $table)
    {
        return sprintf('select ID from SYSOBJECTS where name = %s and SCHID = (select ID from SYSOBJECTS where name = %s and TYPE$ = \'SCH\')', $this->quoteString($table), $this->quoteString($schema));
    }

    /**
     * Compile the query to determine the id of the schema.
     *
     * @param  string  $schema
     * @return string
     */
    public function compileSchemaId($schema)
    {
        return sprintf('select ID from SYSOBJECTS where name = %s and TYPE$ = \'SCH\'', $this->quoteString($schema));
    }

    /**
     * Compile the query to determine the indexes.
     *
     * @param  int  $schemaId
     * @param  int  $tableId
     * @return string
     */
    public function compileIndexes($schemaId, $tableId)
    {
        return sprintf(
            'SELECT DISTINCT IND_OBJ.NAME, IND_OBJ.ID AS INDEXID, INDS.ISUNIQUE, INDS.XTYPE, INDS.GROUPID, INDS.TYPE$ AS TYPE, INDS.INIT_EXTENTS, INDS.BATCH_ALLOC, INDS.MIN_EXTENTS, FBI_DEF(IND_OBJ.ID), IND_OBJ.CRTDATE, SCH_OBJ.ID, SCH_OBJ.NAME AS SCHNAME, TAB_OBJ.ID, TAB_OBJ.NAME AS TABNAME, INDEX_USED_PAGES(IND_OBJ.ID)*(PAGE/1024), IND_OBJ.VALID, INDEX_USED_SPACE(IND_OBJ.ID)*(PAGE/1024), (SELECT MONITORING FROM V$OBJECT_USAGE WHERE INDEX_NAME=IND_OBJ.NAME AND SCH_NAME = SCH_OBJ.NAME) MONITORING, IND_OBJ.INFO7 FROM (SELECT * FROM SYSINDEXES WHERE ROOTFILE != -1 OR (XTYPE & 0X1000) = 0X1000 OR (XTYPE & 0X2000) = 0X2000 OR (XTYPE & 0X08) = 0X08 OR (FLAG & 0X08) = 0X08 OR (XTYPE & 0X8000) = 0X8000 OR (XTYPE & 0X40) = 0X40) INDS, SYSCOLUMNS COLS, (SELECT DISTINCT IND_OBJ_INNER.ID, IND_OBJ_INNER.NAME, IND_OBJ_INNER.CRTDATE, IND_OBJ_INNER.PID, IND_OBJ_INNER.VALID, IND_OBJ_INNER.INFO7 FROM SYSOBJECTS IND_OBJ_INNER WHERE IND_OBJ_INNER.SUBTYPE$ = \'INDEX\') IND_OBJ, (SELECT ID, NAME, SCHID FROM SYSOBJECTS WHERE TYPE$=\'SCHOBJ\' AND SUBTYPE$ LIKE \'_TAB\' AND  ID = %d) TAB_OBJ, (SELECT ID, NAME FROM SYSOBJECTS WHERE TYPE$=\'SCH\' AND  ID = %d) SCH_OBJ WHERE INDS.ID=IND_OBJ.ID AND IND_OBJ.PID=TAB_OBJ.ID AND TAB_OBJ.SCHID=SCH_OBJ.ID  AND COLS.ID = IND_OBJ.PID AND (SF_COL_IS_IDX_KEY(INDS.KEYNUM, INDS.KEYINFO, COLS.COLID)=1 OR (INDS.XTYPE & 0X1000) = 0X1000 OR (INDS.XTYPE & 0X2000) = 0X2000 OR (XTYPE & 0X08) = 0X08) ORDER BY IND_OBJ.NAME ',
            $tableId,
            $schemaId
        );
    }

    /**
     * Compile the query to determine the indexes.
     *
     * @param  int  $indexId
     * @param  int  $tableId
     * @return string
     */
    public function compileIndexColumns($indexId, $tableId)
    {
        return sprintf(
            'SELECT COLS.NAME, COLS.TYPE$, SF_GET_INDEX_KEY_ORDER(INDS.KEYNUM, INDS.KEYINFO, COLS.COLID), SF_GET_INDEX_KEY_SEQ(INDS.KEYNUM, INDS.KEYINFO, COLS.COLID) SEQ FROM SYSCOLUMNS COLS, SYSINDEXES INDS WHERE INDS.ID = %d AND COLS.ID = %d AND SF_COL_IS_IDX_KEY(INDS.KEYNUM, INDS.KEYINFO, COLS.COLID) = 1 ORDER BY SEQ;',
            $indexId,
            $tableId
        );
    }

    /**
     * Compile the query to determine the foreign keys.
     *
     * @param  string  $schema
     * @param  string  $table
     * @return string
     */
    public function compileForeignKeys($schema, $table)
    {
        return sprintf(
            'select col.table_name,col.constraint_name,col.OWNER,listagg(col.column_name, \',\') as COL_NAME from syscons syc, sysobjects syo, all_cons_columns col where syc.id = syo.id and syc.type$ = \'F\' and syo.name = col.constraint_name and table_name = %s  and col.owner= %s group by (col.table_name, col.constraint_name, col.OWNER)',
            $this->quoteString($table),
            $this->quoteString($schema)
        );
    }

    /**
     * Compile the query to determine the foreign references.
     *
     * @param  string  $constraint_name
     * @return string
     */
    public function compileForeignReference($constraint_name)
    {
        return sprintf(
            'select OWNER,CONSTRAINT_NAME,TABLE_NAME,listagg(column_name, \',\') as COLUMNS 
         from all_cons_columns col, (select R_CONSTRAINT_NAME from dba_constraints where constraint_name = %s) con where  col.constraint_name = con.R_CONSTRAINT_NAME
         group by (OWNER,CONSTRAINT_NAME,TABLE_NAME)',
            $this->quoteString($constraint_name)
        );
    }

    /**
     * Compile the columns determine if an auto_increment column.
     *
     * @param  int  $tableId
     * @return string
     */
    public function compileIdentityColumns($tableId)
    {
        return sprintf('select NAME from SYSCOLUMNS where ID = %d and INFO2 & 0x01 = 0x01', $tableId[0]->ID);
    }

    /**
     * Compile the query to determine the columns.
     *
     * @param  string  $schema
     * @param  string  $table
     * @param  int  $tableID
     * @return string
     */
    public function compileColumns($schema, $table, $tableID)
    {
        return sprintf(
            'SELECT NAME, COLID, TYPE$ AS TYPE_NAME, LENGTH$ AS LENGTH, SCALE, NULLABLE$ AS NULLABLE, DEFVAL,(SELECT DISTINCT CYT_NAME FROM SYS.V$CIPHERS WHERE TRUE AND CYT_ID = (SELECT ENC_ID FROM SYSCOLCYT WHERE TID = COL.ID AND CID = COL.COLID)) ENC_NAME,(SELECT ENC_TYPE FROM SYSCOLCYT WHERE TID = COL.ID  AND CID = COL.COLID) ENC_TYPE,(SELECT DISTINCT CYT_NAME FROM SYS.V$CIPHERS WHERE TRUE AND CYT_ID = (SELECT HASH_ID FROM SYSCOLCYT WHERE TID = COL.ID  AND CID = COL.COLID)) HASH_NAME,(SELECT HASH_TYPE FROM SYSCOLCYT WHERE TID = COL.ID  AND CID = COL.COLID) HASH_TYPE, (SELECT CIPHER FROM SYSCOLCYT WHERE TID = COL.ID  AND CID = COL.COLID) CIPHER, (SELECT INFO1 FROM SYSCOLINFOS WHERE ID = COL.ID AND COLID = COL.COLID) VIR_COL, INFO1, (SELECT COMMENT$ FROM SYSCOLUMNCOMMENTS WHERE SCHNAME=%s AND TVNAME=%s AND COLNAME=COL.NAME AND TABLE_TYPE=\'TABLE\') COL_COMMENT FROM (SELECT A.NAME,A.ID,A.COLID, CASE WHEN B.INFO1 IS NULL OR (((B.INFO1>>2) & 0X01)=0 AND ((B.INFO1>>3) & 0X01)=0) THEN A.TYPE$ WHEN (B.INFO2 & 0XFF) = 0 THEN \'NUMBER\' WHEN ((B.INFO1>>3) & 0X01)=1 THEN \'DATE\' ELSE \'FLOAT\' END AS TYPE$,  CASE WHEN B.INFO1 IS NULL OR ((B.INFO1>>2) & 0X01)=0 THEN A.SCALE WHEN (B.INFO2 & 0XFF) = 0 THEN 0 ELSE 129 END AS SCALE,   CASE WHEN B.INFO1 IS NULL OR ((B.INFO1>>2) & 0X01)=0 THEN A.LENGTH$ ELSE (B.INFO2 & 0XFF) END AS LENGTH$,A.NULLABLE$,A.DEFVAL,A.INFO1,A.INFO2 FROM SYSCOLUMNS A LEFT JOIN SYSCOLINFOS B ON A.ID=B.ID AND A.COLID=B.COLID  WHERE A.ID =%d) COL;',
            $this->quoteString($schema),
            $this->quoteString($table),
            $tableID[0]->ID,
        );
    }

    /**
     * Compile the query to dertermine the class name of the type name like 'CLASSxxxxx'.
     *
     * @param  int  $classId
     * @return string
     */
    public function compileClassName($classId)
    {
        return sprintf('SELECT  PKG.NAME as PKG, SCH.NAME as SCH FROM SYSOBJECTS PKG, SYSOBJECTS SCH WHERE PKG.SCHID = SCH.ID AND PKG.TYPE$ = \'SCHOBJ\' AND ( PKG.SUBTYPE$ = \'CLASS\' OR PKG.SUBTYPE$ = \'JCLASS\' OR PKG.SUBTYPE$ = \'TYPE\' ) AND PKG.ID = %d',
            $classId);
    }

    /**
     * Compile the query to determine if the given table exists.
     *
     * @param  string  $schema
     * @param  string  $table
     * @return string
     */
    public function compileTableExists($schema, $table)
    {
        return sprintf(
            'select (case when object_id(%s, \'U\') is null then 0 else 1 end) as "exists"',
            $this->quoteString($schema ? $schema.'.'.$table : $table)
        );
    }

    /**
     * Compile the query to determine the tables.
     *
     * @param  string  $schema
     * @param  int  $schemaId
     * @return string
     */
    public function compileTables($schema, $schemaId)
    {
        return 'select TABLE_USED_PAGES(\''.$schema.'\', tab_obj_out.NAME)*PAGE TABLE_USED,tab_obj_out.*, comment_obj.comment$ as COMMENTS from
        (select  TAB_OBJ.NAME, TAB_OBJ.ID, TAB_OBJ.SUBTYPE$, TAB_OBJ.INFO3, TAB_OBJ.SCHID, SCH_OBJ.NAME SCHNAME, TAB_OBJ.CRTDATE, INFO8, TAB_OBJ.INFO2*(PAGE/1024)/1024, TAB_OBJ.INFO1, TAB_OBJ.INFO6 from (select TAB_OBJ_INNER.NAME, TAB_OBJ_INNER.ID, TAB_OBJ_INNER.SUBTYPE$, TAB_OBJ_INNER.INFO1, TAB_OBJ_INNER.INFO2, TAB_OBJ_INNER.INFO3, TAB_OBJ_INNER.INFO6, TAB_OBJ_INNER.INFO8, TAB_OBJ_INNER.SCHID, TAB_OBJ_INNER.CRTDATE from SYSOBJECTS TAB_OBJ_INNER , SYSOBJECTS SCH_OBJ_INNER, SYSOBJECTS USER_OBJ_INNER where TAB_OBJ_INNER.type$ = \'SCHOBJ\'  and TAB_OBJ_INNER.INFO3&0x100000!=0x100000 and TAB_OBJ_INNER.INFO3&0x200000!=0x200000 and TAB_OBJ_INNER.INFO3 & 0x003F not in (0x0A, 0x20) and (TAB_OBJ_INNER.INFO3 & 0x100000000) = 0  and TAB_OBJ_INNER.NAME not like \'CTI$%$_\' and TAB_OBJ_INNER.NAME not like \'%$AUX\' and TAB_OBJ_INNER.NAME not like \'%$_AUX\' and TAB_OBJ_INNER.NAME not like \'%$ALOG\' and TAB_OBJ_INNER.NAME not like \'BIN$%\' and TAB_OBJ_INNER.SUBTYPE$ = \'UTAB\' and (TAB_OBJ_INNER.PID=-1 or TAB_OBJ_INNER.PID=0) and TAB_OBJ_INNER.INFO3 & 0x003F != 13 and TAB_OBJ_INNER.SCHID = '.$schemaId->ID.' and USER_OBJ_INNER.SUBTYPE$ = \'USER\' and SCH_OBJ_INNER.ID = TAB_OBJ_INNER.SCHID and SCH_OBJ_INNER.PID = USER_OBJ_INNER.ID and SF_CHECK_PRIV_OPT(UID(), CURRENT_USERTYPE(), TAB_OBJ_INNER.ID, USER_OBJ_INNER.ID, USER_OBJ_INNER.INFO1, TAB_OBJ_INNER.ID) = 1) TAB_OBJ, (select ID, NAME from SYSOBJECTS where TYPE$=\'SCH\' and  ID = '.$schemaId->ID.') SCH_OBJ where TAB_OBJ.SCHID=SCH_OBJ.ID ) TAB_OBJ_OUT LEFT JOIN SYSTABLECOMMENTS COMMENT_OBJ ON TAB_OBJ_OUT.NAME = COMMENT_OBJ.TVNAME AND TAB_OBJ_OUT.SCHNAME = COMMENT_OBJ.SCHNAME order by TAB_OBJ_OUT.NAME;
        ';
    }

    /**
     * Compile the query to determine the views.
     *
     * @param  string  $schema
     * @return string
     */
    public function compileViews($schema)
    {
        return sprintf('select  VIEW_OBJ.ID, VIEW_OBJ.NAME, VIEW_OBJ.CRTDATE, VIEW_OBJ.INFO1&0x03 VIEW_CHECKED, VIEW_OBJ.INFO1&0X10, SF_VIEW_IS_UPDATABLE(SCH_OBJ.NAME, VIEW_OBJ.NAME) VIEW_UPDATABLE, VIEW_TXT.DEFINATION, SCH_OBJ.ID, SCH_OBJ.NAME AS SCHNAME, VIEW_OBJ.VALID, 
        (select COMMENT$ from SYSTABLECOMMENTS where table_type=\'VIEW\' and SCHNAME=SCH_OBJ.NAME and TVNAME=VIEW_OBJ.NAME) VIEW_COMMENT from (select ID, NAME from SYSOBJECTS where TYPE$=\'SCH\' and  ID = (select id from sysobjects where TYPE$ = \'SCH\' and NAME=%s)) SCH_OBJ, 
        (select distinct VIEW_OBJ_INNER.ID, VIEW_OBJ_INNER.NAME, VIEW_OBJ_INNER.INFO1, VIEW_OBJ_INNER.CRTDATE, VIEW_OBJ_INNER.SCHID, VIEW_OBJ_INNER.VALID from SYSOBJECTS VIEW_OBJ_INNER, SYSOBJECTS SCH_OBJ_INNER, SYSOBJECTS USER_OBJ_INNER where VIEW_OBJ_INNER.SUBTYPE$=\'VIEW\' 
        and (VIEW_OBJ_INNER.INFO1 & 0x001FFFE0)=0 and (VIEW_OBJ_INNER.INFO1 & 0x10)=0 and USER_OBJ_INNER.SUBTYPE$ = \'USER\' and SCH_OBJ_INNER.ID = VIEW_OBJ_INNER.SCHID and SCH_OBJ_INNER.PID = USER_OBJ_INNER.ID and SF_CHECK_PRIV_OPT(UID(), CURRENT_USERTYPE(), VIEW_OBJ_INNER.ID, USER_OBJ_INNER.ID, USER_OBJ_INNER.INFO1, VIEW_OBJ_INNER.ID) = 1) VIEW_OBJ,
        (select id, seqno, txt as defination from systexts where seqno = 0) VIEW_TXT  where SCH_OBJ.ID=VIEW_OBJ.SCHID and VIEW_OBJ.ID=VIEW_TXT.ID ORDER BY VIEW_OBJ.NAME',
            $this->quoteString($schema));
    }

    /**
     * Compile an add column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add column(%s)',
            $this->wrapTable($blueprint),
            $this->getColumn($blueprint, $command->column)
        );
    }

    /**
     * Compile a primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add constraint %s primary key (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        $sql = sprintf('alter table %s add constraint %s unique (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );

        return $sql;
    }

    /**
     * Compile a plain index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return 'create index '.$this->wrap($command->index).' on '.$this->wrapTable($blueprint).' ('.$this->columnize($command->columns).')';
    }

    /**
     * Compile a spatial index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return 'create spatial index '.$this->wrap($command->index).' on '.$this->wrapTable($blueprint).' ('.$this->columnize($command->columns).')';
    }

    /**
     * Compile a drop table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @return string
     */
    public function compileDropAllTables()
    {
        return 'BEGIN FOR c IN (SELECT table_name FROM user_tables where table_name not like \'%HISTOGRAMS_TABLE\') LOOP EXECUTE IMMEDIATE (\'DROP TABLE "\' || c.table_name || \'" CASCADE\'); END LOOP; END;';
    }

    /**
     * Compile the SQL needed to drop all views.
     *
     * @param  array  $views
     * @return string
     */
    public function compileDropAllViews($views)
    {
        $view_sql = 'select \''.$this->wrapArray($views)[0].'\' as view_name';
        for ($i = 1; $i < count($views); $i++) {
            $view_sql = $view_sql.' union select \''.$this->wrapArray($views)[$i].'\' as view_name';
        }

        return 'BEGIN FOR c IN ('.$view_sql.') LOOP EXECUTE IMMEDIATE (\'DROP VIEW "\' || c.view_name || \'" CASCADE\'); END LOOP; END;';
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table if exists '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        // $columns = $this->wrapArray($command->columns);
        $columns = $command->columns;

        $table = $this->wrapTable($blueprint);

        // return 'alter table '.$table.' drop ( '.implode(', ', $columns).' )';

        $colSql = 'select '.$this->quoteString($columns[0]).' as COL';

        for ($i = 1; $i < count($columns); $i++) {
            $colSql = $colSql.' union select '.$this->quoteString($columns[$i]);
        }

        return'BEGIN FOR c IN ('.$colSql.') LOOP EXECUTE IMMEDIATE (\'alter table '.$table.' drop "\' || c.COL || \'"\'); END LOOP; END;';
    }

    /**
     * Compile a drop primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'primary');
    }

    /**
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @param  string  $type
     * @return string
     */
    private function dropConstraint(Blueprint $blueprint, Fluent $command, $type)
    {
        $table = $this->wrapTable($blueprint);
        $index = $this->wrap(substr($command->index, 0, 30));

        if ($type === 'index') {
            $sql = "drop index {$index}";

            return $sql;
        }

        $sql = "alter table {$table} drop constraint {$index}";

        return $sql;
    }

    /**
     * Compile a drop unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'unique');
    }

    /**
     * Compile a drop index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'index');
    }

    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'index');
    }

    /**
     * Compile a drop foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        return $this->dropConstraint($blueprint, $command, 'foreign');
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to ".$this->wrapTable($command->to);
    }

    /**
     * Compile a rename column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $table = $this->wrapTable($blueprint);

        $rs = [];
        $rs[0] = 'alter table '.$table.' alter column '.$this->wrap($command->from).' rename to '.$this->wrap($command->to);

        return (array) $rs;
    }

    /**
     * Compile a rename index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter index %s rename to %s',
            $this->wrap($command->from),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array|string
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $column = $command->column;

        $sql = sprintf('alter table %s modify %s %s',
            $this->wrapTable($blueprint),
            $this->wrap($column),
            $this->getType($column)
        );

        return $this->addModifiers($sql, $blueprint, $column);
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints()
    {
        return 'SET_SESSION_CONS_CHK(1);';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'SET_SESSION_CONS_CHK(0);';
    }

    /**
     * Compile a comment command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileComment(Blueprint $blueprint, Fluent $command)
    {
        if (! is_null($comment = $command->column->comment) || $command->column->change) {
            return sprintf('comment on column %s.%s is %s',
                $this->wrapTable($blueprint),
                $this->wrap($command->column->name),
                is_null($comment) ? 'NULL' : "'".str_replace("'", "''", $comment)."'"
            );
        }
    }

    /**
     * Compile a table comment command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileTableComment(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('comment on table %s is %s',
            $this->wrapTable($blueprint),
            "'".str_replace("'", "''", $command->comment)."'"
        );
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return (! is_null($column->length)) ? "char({$column->length})" : 'char';
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        if ($column->length) {
            return "varchar({$column->length})";
        }

        return 'varchar';
    }

    /**
     * Create the column definition for a tiny text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTinyText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        return 'bigint';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column)
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column)
    {
        return 'smallint';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column)
    {
        return 'tinyint';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeFloat(Fluent $column)
    {
        if ($column->precision) {
            return "float({$column->precision})";
        }

        return 'float';
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDouble(Fluent $column)
    {
        return 'double';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDecimal(Fluent $column)
    {
        return "decimal({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBoolean(Fluent $column)
    {
        return 'bit';
    }

    /**
     * Create the column definition for a enum type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        return sprintf(
            'varchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDate(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTime(Fluent $column)
    {
        if ($column->useCurrent) {
            $column->default(new Expression('CURRENT_TIMESTAMP'));
        }

        return (! is_null($column->precision)) ? "datetime($column->precision)" : 'datetime';
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column)
    {
        if ($column->useCurrent) {
            $column->default(new Expression('CURRENT_TIMESTAMP'));
        }

        return (! is_null($column->precision)) ? "datetime($column->precision) with time zone" : 'datetime with time zone';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return $column->precision ? "time($column->precision)" : 'time';
    }

    /**
     * Create the column definition for a timetz type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimeTz(Fluent $column)
    {
        return $column->precision ? "time($column->precision) with time zone" : 'time with time zone';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        if ($column->useCurrent) {
            $column->default(new Expression('CURRENT_TIMESTAMP'));
        }

        return (! is_null($column->precision)) ? "timestamp($column->precision)" : 'timestamp';
    }

    /**
     * Create the column definition for a timestamp type with timezone.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        if ($column->useCurrent) {
            $column->default(new Expression('CURRENT_TIMESTAMP'));
        }

        return (! is_null($column->precision)) ? "timestamp($column->precision) with time zone" : 'timestamp with time zone';
    }

    /**
     * Create the column definition for a year type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeYear(Fluent $column)
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBinary(Fluent $column)
    {
        if (! is_null($column->length)) {
            return $column->fixed ? "binary({$column->length})" : "varbinary({$column->length})";
        }

        return 'blob';
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'char(36)';
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeIpAddress(Fluent $column)
    {
        return 'varchar(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column)
    {
        return 'varchar(17)';
    }

    /**
     * Create the column definition for a json type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'clob';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeometry(Fluent $column)
    {
        if ($column->subtype) {
            return sprintf('SYSGEO2.ST_Geometry %s %s',
                'CHECK(type = '.$column->subtype.')',
                $column->srid ? 'CHECK(srid = '.$column->srid.')' : ''
            );
        }

        return 'SYSGEO2.ST_Geometry';
    }

    /**
     * Create the column definition for a spatial Geography type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeography(Fluent $column)
    {
        if ($column->subtype) {
            return sprintf('SYSGEO2.ST_Geography %s %s',
                'CHECK(type = '.$column->subtype.')',
                $column->srid ? 'CHECK(srid = '.$column->srid.')' : ''
            );
        }

        return 'SYSGEO2.ST_Geography';
    }

    /**
     * Get the SQL for a collation column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->collation)) {
            return " collate {$column->collation}";
        }
    }

    /**
     * Get the SQL for a generated virtual column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyVirtualAs(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($virtualAs = $column->virtualAsJson)) {
            if ($this->isJsonSelector($virtualAs)) {
                $virtualAs = $this->wrapJsonSelector($virtualAs);
            }

            return " as ({$virtualAs})";
        }

        if (! is_null($virtualAs = $column->virtualAs)) {
            return " as ({$this->getValue($virtualAs)})";
        }
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->virtualAs) || ! is_null($column->virtualAsJson)) {
            if ($column->nullable === false) {
                return ' not null';
            } else {
                return;
            }
        }

        $null = $column->nullable ? ' null' : ' not null';

        return $null;
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        // implemented @modifyNullable
        // return '';

        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if (! $column->change && in_array($column->type, $this->serials) && $column->autoIncrement) {
            return $this->hasCommand($blueprint, 'primary') ? ' identity' : ' identity primary key';
        }
    }

    /**
     * Get the SQL for a "comment" column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyComment(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->comment)) {
            return " comment '".addslashes($column->comment)."'";
        }
    }
}
