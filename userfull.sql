-- select (sum(p.pnl)-sum(p.commission))/100000000
-- from position p
-- where p.pnl is not null


-- 209.88787273
-- select (sum(sub.pnl)-sum(sub.commission))/100000000
-- from (
--     select p.pnl, p.commission, i.direction, c.original_message
--     from position p
--     left join intent i on i.id = p.intent_id
--     left join confirmation c on c.id = (
--         select cs.id
--         from confirmation cs
--         where cs.intent_id = i.id
--         order by cs.created_at
--         limit 1
--     )
--     where p.status = 'closed'
--     and (
--      (i.direction = 'long' and c.original_message ilike '%Buying coins%')
--          or (i.direction = 'short' and c.original_message ilike '%Selling coins%')
--      )
--     order by p.pnl desc
-- ) sub

-- select p.pnl, p.commission, i.direction, c.original_message, extract(epoch from c.created_at - i.created_at) / 60
-- from position p
--          left join intent i on i.id = p.intent_id
--          left join confirmation c on c.id = (
--     select cs.id
--     from confirmation cs
--     where cs.intent_id = i.id
--       and case
--               when i.direction = 'long' then cs.original_message ilike '%Buying coins%'
--               when i.direction = 'sell' then cs.original_message ilike '%Selling coins%'
--               else true
--         end
--     order by cs.created_at
--     limit 1
-- )
-- where p.status = 'closed'
--   and (
--     (i.direction = 'long' and c.original_message ilike '%Buying coins%')
--         or (i.direction = 'short' and c.original_message ilike '%Selling coins%')
--     )
-- order by p.pnl desc

-- select cs.id, cs.original_message, i.direction, i.id
-- from confirmation cs
-- left join public.intent i on i.id = cs.intent_id
-- where cs.intent_id = '1ef8f802-9dec-618a-a6d9-3fdc8b05dc17'
--     and case
--         when i.direction = 'long' then cs.original_message ilike '%Buying coins%'
--         when i.direction = 'sell' then cs.original_message ilike '%Selling coins%'
--         else true
--     end
-- order by cs.created_at
-- limit 1


-- select extract(epoch from now() - p.created_at) / 60 / 60, t.name
-- from position p
-- left join intent i on i.id = p.intent_id
-- left join ticker t on t.id = i.ticker_id
-- where p.status = 'open' and (extract(epoch from now() - p.created_at) / 60 / 60) > 8


select t.name, i.direction, i.initial_direction, i.amount, p.pnl,
       i.confirmation_trades_cost_buy,
       i.confirmation_trades_cost_sell,
       i.on_position_trades_cost_buy,
       i.on_position_trades_cost_sell
from intent i
         left join position p on i.id = p.intent_id
         left join ticker t on t.id = i.ticker_id
where
    i.status = 'closed'
  and i.confirmation_trades_cost_buy is not null
  and i.on_position_trades_cost_buy is not null
