import urllib.parse
from aiogram import Router, F
from aiogram.types import Message, InlineKeyboardMarkup, InlineKeyboardButton
from aiogram.filters import CommandStart, Command
from database.crud import UserDB, ActiveSessionDB, PrivilegedDB
from utils.helpers import generate_unique_code

router = Router()

@router.message(CommandStart())
async def cmd_start(message: Message):
    user_id = message.from_user.id
    args = message.text.split()[1] if len(message.text.split()) > 1 else None

    # Agar foydalanuvchi havolani bosib (deep-link) kirgan bo'lsa
    if args:
        target_user = await UserDB.get_user_by_code(args)
        if not target_user:
            await message.answer("❌ Bu havola noto'g'ri yoki eskirgan.\nBotdan foydalanish uchun /start buyrug'ini bosing.")
            return

        if target_user.user_id == user_id:
            await show_main_menu(message)
            return

        await ActiveSessionDB.set_session(user_id, target_user.user_id)
        target_name = target_user.first_name if target_user.first_name else "Foydalanuvchi"
        text = (
            f"👻 <b>Anonim rejim faollashtirildi!</b>\n\n"
            f"Siz hozir <b>{target_name}</b> ga anonim xabar yozmoqdasiz.\n\n"
            "📝 Xabaringizni yozing — u anonim tarzda yetkaziladi.\n"
            "📎 Matn, rasm, video, audio — barchasi qo'llab-quvvatlanadi.\n\n"
            "🔙 Bekor qilish uchun: /cancel\n"
            "🏠 Bosh menyu: /start"
        )
        await message.answer(text, parse_mode="HTML")
        return

    # Oddiy /start
    await ActiveSessionDB.delete_session(user_id)
    await show_main_menu(message)

async def show_main_menu(message: Message):
    user = message.from_user
    user_id = user.id
    
    user_data = await UserDB.get_user(user_id)
    if not user_data:
        code = generate_unique_code()
        await UserDB.save_user(
            user_id, 
            username=user.username, 
            first_name=user.first_name, 
            last_name=user.last_name, 
            unique_code=code
        )
        user_data = await UserDB.get_user(user_id)

    unique_code = user_data.unique_code
    bot_info = await message.bot.get_me()
    bot_username = bot_info.username
    link = f"https://t.me/{bot_username}?start={unique_code}"
    
    is_priv = await PrivilegedDB.is_privileged(user_id)

    welcome_text = (
        f"👋 <b>Assalomu alaykum, {user.first_name}!</b>\n\n"
        "🤖 Bu bot orqali siz <b>anonim xabarlar</b> qabul qilishingiz mumkin.\n\n"
        "🔗 <b>Sizning shaxsiy havolangiz:</b>\n"
        f"<code>{link}</code>\n\n"
        "☝️ Bu havolani do'stlaringiz, ijtimoiy tarmoqlarda yoki bio'ngizda ulashing.\n"
        "Havolani bosgan har bir inson sizga <b>anonim xabar</b> yuborishi mumkin!\n\n"
    )

    if is_priv:
        welcome_text += (
            "🔐 <b>Sizda maxsus huquq mavjud!</b>\n"
            "Sizga kelgan anonim xabarlarning yuboruvchisini ko'rishingiz mumkin.\n\n"
        )

    welcome_text += (
        "📋 <b>Buyruqlar:</b>\n"
        "├ /start — Bosh menyu\n"
        "├ /help — Yordam\n"
        "├ /myid — Sizning Telegram ID\n"
        "├ /mylink — Sizning havola\n"
        "└ /cancel — Anonim yozishni bekor qilish"
    )

    if is_priv:
        welcome_text += (
            "\n\n🛡️ <b>Admin buyruqlari:</b>\n"
            "├ /grant <user_id> — Huquq berish\n"
            "├ /revoke <user_id> — Huquqni olish\n"
            "├ /admins — Ishonchli insonlar\n"
            "├ /block <user_id> — Bloklash\n"
            "├ /unblock <user_id> — Blokdan chiqarish\n"
            "└ /stats — Statistika"
        )

    share_url = f"https://t.me/share/url?url={urllib.parse.quote(link)}&text={urllib.parse.quote('Menga anonim xabar yuboring 👻')}"
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="🔗 Havolani ulashish", url=share_url)]
    ])

    await message.answer(welcome_text, parse_mode="HTML", reply_markup=keyboard)

@router.message(Command("help"))
async def cmd_help(message: Message):
    user_id = message.from_user.id
    is_priv = await PrivilegedDB.is_privileged(user_id)
    
    text = (
        "📖 <b>Botdan foydalanish yo'riqnomasi</b>\n\n"
        "1️⃣ /start buyrug'ini bosing va shaxsiy havolangizni oling\n"
        "2️⃣ Havolani do'stlaringizga yuboring\n"
        "3️⃣ Ular havola orqali sizga anonim xabar yozadi\n"
        "4️⃣ Siz xabarga reply qiling — javob yuboruvchiga boradi\n\n"
        "💡 <b>Muhim:</b>\n"
        "• Rasm, video, audio, sticker — barchasi qo'llab-quvvatlanadi\n"
        "• Anonim xabarga reply qiling — javob yuboruvchiga qaytadi\n"
        "• /cancel — anonim yozish rejimini bekor qiladi"
    )

    if is_priv:
        text += (
            "\n\n🛡️ <b>Admin buyruqlari:</b>\n"
            "• /grant <user_id> — foydalanuvchiga ko'rish huquqi berish\n"
            "• /revoke <user_id> — huquqni olib tashlash\n"
            "• /admins — ishonchli insonlar ro'yxati\n"
            "• /block <user_id> — foydalanuvchini bloklash\n"
            "• /unblock <user_id> — blokdan chiqarish\n"
            "• /stats — bot statistikasi\n"
            "• /broadcast <xabar> — barchaga xabar yuborish"
        )
    await message.answer(text, parse_mode="HTML")

@router.message(Command("myid"))
async def cmd_myid(message: Message):
    await message.answer(f"🆔 <b>Sizning Telegram ID:</b>\n<code>{message.from_user.id}</code>", parse_mode="HTML")

@router.message(Command("mylink"))
async def cmd_mylink(message: Message):
    user_id = message.from_user.id
    user_data = await UserDB.get_user(user_id)
    
    if not user_data:
        await message.answer("❌ Avval /start buyrug'ini bosing.")
        return
        
    bot_info = await message.bot.get_me()
    link = f"https://t.me/{bot_info.username}?start={user_data.unique_code}"
    
    share_url = f"https://t.me/share/url?url={urllib.parse.quote(link)}&text={urllib.parse.quote('Menga anonim xabar yuboring 👻')}"
    keyboard = InlineKeyboardMarkup(inline_keyboard=[
        [InlineKeyboardButton(text="🔗 Havolani ulashish", url=share_url)]
    ])
    
    text = (
        "🔗 <b>Sizning shaxsiy havolangiz:</b>\n\n"
        f"<code>{link}</code>\n\n"
        "Bu havolani ulashib, anonim xabarlar qabul qiling! 👻"
    )
    await message.answer(text, parse_mode="HTML", reply_markup=keyboard)

@router.message(Command("cancel"))
async def cmd_cancel(message: Message):
    user_id = message.from_user.id
    has_session = await ActiveSessionDB.get_session(user_id)
    
    if has_session:
        await ActiveSessionDB.delete_session(user_id)
        await message.answer("✅ Anonim yozish rejimi bekor qilindi.\n🏠 Bosh menyu uchun: /start")
    else:
        await message.answer("ℹ️ Siz hozir hech kimga anonim yozmayapsiz.\n🏠 Bosh menyu uchun: /start")
