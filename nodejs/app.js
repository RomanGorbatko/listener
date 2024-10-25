'use strict';

import dotenv from 'dotenv'
import ccxt from 'ccxt'
import { createClient } from 'redis'
import pg from 'pg'
const { Client } = pg

dotenv.config({path: `../.env`});
dotenv.config({path: `../.env.local`, override: true});

const postgresClient = new Client({
    connectionString: process.env['DATABASE_URL'].split('?')[0]
})

const redisClient = createClient({
    url: process.env['REDIS_URL']
});


let watchIntentTickers = []
let secondsBlocker = false

async function watchTrades (exchange, symbol) {
    while (true) {
        if ((new Date().getSeconds() % 10) === 0 && secondsBlocker === false) {
            secondsBlocker = true
            try {
                let tickers = await postgresClient.query(
                    'select t.name, i.status ' +
                    'from intent i ' +
                    'left join ticker t on i.ticker_id = t.id ' +
                    'where i.status in (\'waiting_for_confirmation\', \'on_position\')'
                )

                watchIntentTickers = []
                for (let ticker of tickers.rows) {
                    watchIntentTickers[ticker.name + '/USDT:USDT'] = {
                        status: ticker.status
                    }

                    const key = 'trades_'
                        + ticker.name + '_'
                        + ticker.status + '_'

                    if (!await redisClient.exists(key + 'buy')) {
                        await redisClient.set(key + 'buy', 0)
                    }

                    if (!await redisClient.exists(key + 'sell')) {
                        await redisClient.set(key + 'sell', 0)
                    }
                }

            } catch (e) {
                console.error(e)
            }

        } else if ((new Date().getSeconds() % 10) !== 0)  {
            secondsBlocker = false
        }

        try {
            const trades = await exchange.watchTrades (symbol)
            for (const trade of trades) {
                const ticker = trade.symbol.split('/')[0]

                if (watchIntentTickers[trade.symbol] !== undefined) {
                    const key = 'trades_'
                        + ticker + '_'
                        + watchIntentTickers[trade.symbol]['status'] + '_'
                        + trade.side

                    if (await redisClient.exists(key)) {
                        await redisClient.incrByFloat(key, trade.cost)
                    }
                }
            }
        } catch (e) {
            console.error(symbol, e)

            throw new Error(e)
        }
    }
}

async function main () {
    await redisClient.connect();
    await postgresClient.connect()

    const redisSymbols = await redisClient.sMembers('binanceFutures_markets')
    const symbols = redisSymbols.map((item) => item + '/USDT:USDT')
    const exchange = new ccxt.pro.binanceusdm({
        'newUpdates': true,
    })
    exchange.verbose = false
    await Promise.all (symbols.map ((symbol) => watchTrades (exchange, symbol)))
}

main()
    .catch(console.error)
