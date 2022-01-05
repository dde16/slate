# Change Log

## 3.1.3

### Abstract
- Converted ORM from native joining to manual

### Current System (Native Joining)

This current system joins the same way you would normally but in order to depth limit has a few hacks.

On joins, to depth limit you can use this method, where the order by is optional.

```sql
ROW_NUMBER() OVER (PARTITION BY primarykey ORDER BY `primarykey`)
```

This led to a whole load of hacks, which I dont want to get into as they are exhaustive and at time abstract and difficult to explain. 

### New System (Manual Joining)

The new system is based on using manual joining.

Where this is a OneToMany relationship, **not** a BelongsToMany relationship, take the example:

`table1 INNER JOIN table2`

Here, we would
- Fetch table1's primary keys
- Select all table2 rows where the foreign key equals the primary keys from table1
- Do this using cursors to minimise performance hit

This is miles simpler as, each step in the query is just one select query - meaning no hacks for limiting or order by.

For multi-relationship queries, optimisations have been made
- All relationships have their query statements prepared only once.
- All inner joins will be performed first to ensure they match before performing left joins. 
- Relationships are performed per-row rather than aggregations on tables, this is so large selects with inner joins/left joins are less memory intensive when fetching a large amount of primary keys.

#### Performance

Performance of the New System is essentially a function of the amount of data you are fetching. This is because in native joins the DBMS plans out your inner join to make optimisations to it and will always be better than multiple queries. 

However, this is negated when taking into account that in development you will not be fetching of whole tables, rather limiting for pagination or getting a single row.

### New System vs Old System

| Feature             | New System   | Old System                    |
| ------------------- | ------------ | ----------------------------- |
| Joins               | Manual       | Native                        |
| Complexity          | Simpler      | Complex + hacks               |
| Maintainability     | More         | Less                          |
| Cross-compatibility | More         | Less (ROW_NUMBER/Var hack)    |
| Performance         | ~log(n)      | More consistent, less overall |

### Testing

For testing, I used predefined relationships in my CRM project where each table has ~10k rows (such as Person -> Account).

### compatibility

100% compatibility is maintained in
- Query planning and options
- Relationships

However, there are things that wont work
- Cannot print full SQL queries
- Just-in-time relationships are currently not in effect