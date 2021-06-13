Table post-delivery-data
===========
Delivery data for items

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES | PRI |  |  |    
| postopts | External post connectors add their network name to this comma-separated string to identify that they should be delivered to these networks during delivery | text | NO |  |  |  |    
| inform | Additional receivers of the linked item | mediumtext | NO |  |  |  |    
| queue_count | Initial number of delivery recipients, used as item.delivery_queue_count | mediumint | YES |  | 0 |  |    
| queue_done | Number of successful deliveries, used as item.delivery_queue_done | mediumint | YES |  | 0 |  |    
| queue_failed | Number of unsuccessful deliveries, used as item.delivery_queue_failed | mediumint | YES |  | 0 |  |    
| activitypub | Number of successful deliveries via ActivityPub | mediumint | YES |  | 0 |  |    
| dfrn | Number of successful deliveries via DFRN | mediumint | YES |  | 0 |  |    
| legacy_dfrn | Number of successful deliveries via legacy DFRN | mediumint | YES |  | 0 |  |    
| diaspora | Number of successful deliveries via Diaspora | mediumint | YES |  | 0 |  |    
| ostatus | Number of successful deliveries via OStatus | mediumint | YES |  | 0 |  |    

Return to [database documentation](help/database)
