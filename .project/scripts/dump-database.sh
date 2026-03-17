#!/bin/bash
DATABASE=${1}
DUMP_CONTEXT=${2}
DB_FILE=db_${DATABASE}.sql
if [ "$DUMP_CONTEXT" = "project" ]; then
    MYSQL_DUMP_DIR=.project/data/dumps
elif [ "$DUMP_CONTEXT" = "test" ]; then
    MYSQL_DUMP_DIR=test/tests/_data/dumps
else
    echo "You must specify a valid context for dumping. Please use 'project' or 'test' as second argument for the script"
    exit 1
fi
EXCLUDED_TABLES=(
backend_layout
be_dashboards
be_sessions
cache_adminpanel_requestcache
cache_adminpanel_requestcache_tags
cache_hash
cache_hash_tags
cache_in2publish
cache_in2publish_core
cache_in2publish_core_tags
cache_in2publish_tags
cache_news_category
cache_news_category_tags
cache_pages
cache_pages_tags
cache_rootline
cache_rootline_tags
cache_tx_solr
cache_tx_solr_configuration
cache_tx_solr_configuration_tags
cache_tx_solr_tags
cache_workspaces_cache
cache_workspaces_cache_tags
fe_sessions
index_config
index_debug
index_fulltext
index_grlist
index_phash
index_rel
index_section
index_stat_word
index_words
sys_file_processedfile
sys_history
sys_http_report
sys_lockedrecords
sys_log
sys_messenger_messages
sys_refindex
tx_in2code_rpc_request
tx_in2publish_notification
tx_in2code_in2publish_task
tx_in2publishcore_log
tx_in2code_rpc_data
tx_in2publishcore_filepublisher_instruction
tx_in2publishcore_running_request
tx_solr_eventqueue_item
tx_solr_indexqueue_file
tx_solr_indexqueue_indexing_property
tx_solr_indexqueue_item
tx_solr_last_searches
tx_solr_statistics
tx_in2publish_wfpn_demand
tx_in2accordion_item
)

IGNORED_TABLES_STRING=''
for TABLE in "${EXCLUDED_TABLES[@]}"
do :
   IGNORED_TABLES_STRING+=" --ignore-table=${DATABASE}.${TABLE}"
done

echo "Dump db structure"
docker compose exec mysql /usr/bin/mysqldump -u root --password=root --single-transaction --no-data ${IGNORED_TABLES_STRING} ${DATABASE} > ${MYSQL_DUMP_DIR}/${DB_FILE}

echo "Dump db content"
docker compose exec mysql /usr/bin/mysqldump -u root --password=root ${DATABASE} --no-create-info ${IGNORED_TABLES_STRING} >> ${MYSQL_DUMP_DIR}/${DB_FILE}
