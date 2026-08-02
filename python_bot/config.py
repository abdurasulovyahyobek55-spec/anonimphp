import os
from dotenv import load_dotenv

load_dotenv()

BOT_TOKEN = os.getenv("BOT_TOKEN", "")
ADMIN_ID = int(os.getenv("ADMIN_ID", 0))

# Agar DATABASE_URL ko'rsatilmagan bo'lsa, SQLite ishlatiladi
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite+aiosqlite:///data/anonim.db")

# Agar postgres:// orqali ulanish berilgan bo'lsa (masalan Render), asyncpg ga o'zgartiramiz
if DATABASE_URL.startswith("postgres://"):
    DATABASE_URL = DATABASE_URL.replace("postgres://", "postgresql+asyncpg://", 1)

# Render tomonidan avtomatik taqdim etiladigan port, yo'q bo'lsa 10000
PORT = int(os.getenv("PORT", 10000))

# Webhook manzili (masalan: https://your-app.onrender.com)
WEBHOOK_URL = os.getenv("WEBHOOK_URL", "")
