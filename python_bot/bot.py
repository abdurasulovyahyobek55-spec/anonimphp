import asyncio
import logging
from aiogram import Bot, Dispatcher
from aiogram.client.default import DefaultBotProperties
from aiohttp import web
from aiogram.webhook.aiohttp_server import SimpleRequestHandler, setup_application

from config import BOT_TOKEN, PORT, WEBHOOK_URL
from database.crud import init_db
from handlers.start import router as start_router
from handlers.admin import router as admin_router
from handlers.messages import router as messages_router

# Logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")

# Bot va Dispatcher yaratish
bot = Bot(token=BOT_TOKEN, default=DefaultBotProperties(parse_mode='HTML'))
dp = Dispatcher()

# Routerni ulash (messages eng ohirida bo'lishi kerak, chunki u hamma xabarni ushlaydi)
dp.include_router(start_router)
dp.include_router(admin_router)
dp.include_router(messages_router)

# Asosiy veb sahifa (Render uxlab qolmasligi uchun)
async def index_handler(request):
    return web.Response(text="Bot is running!")

async def on_startup(bot: Bot):
    await init_db()
    logging.info("Ma'lumotlar bazasi initsializatsiya qilindi.")
    if WEBHOOK_URL:
        await bot.set_webhook(f"{WEBHOOK_URL}/webhook")
        logging.info(f"Webhook o'rnatildi: {WEBHOOK_URL}/webhook")
    else:
        await bot.delete_webhook(drop_pending_updates=True)
        logging.info("Webhook o'chirildi. Long Polling rejimida ishlanmoqda.")

def main():
    if not BOT_TOKEN:
        logging.error("BOT_TOKEN topilmadi!")
        return

    dp.startup.register(on_startup)

    app = web.Application()
    app.router.add_get("/", index_handler)

    if WEBHOOK_URL:
        # Webhook rejimi
        SimpleRequestHandler(dispatcher=dp, bot=bot).register(app, path="/webhook")
        setup_application(app, dp, bot=bot)
        logging.info(f"Veb-server (Webhook) {PORT} portida ishga tushirilmoqda...")
        web.run_app(app, host="0.0.0.0", port=PORT)
    else:
        # Long Polling + Background Web Server rejimi (Render "Port binding" xatosi bermasligi uchun)
        logging.info(f"Veb-server (Dummy) {PORT} portida va Long Polling ishga tushirilmoqda...")
        
        async def run_polling_and_server():
            runner = web.AppRunner(app)
            await runner.setup()
            site = web.TCPSite(runner, '0.0.0.0', PORT)
            await site.start()
            
            await dp.start_polling(bot)
            
        asyncio.run(run_polling_and_server())

if __name__ == "__main__":
    main()
