from aiogram import Router, F
from aiogram.types import Message
from aiogram.filters import Command
from database.crud import UserDB, PrivilegedDB, MessageDB
from config import ADMIN_ID

router = Router()

async def is_main_admin(user_id: int) -> bool:
    return user_id == ADMIN_ID

@router.message(Command("grant"))
async def cmd_grant(message: Message):
    if not await is_main_admin(message.from_user.id):
        return
        
    args = message.text.split(maxsplit=1)
    if len(args) < 2:
        await message.answer("📋 <b>Foydalanish:</b>\n<code>/grant 123456789</code> — ID bo'yicha\n<code>/grant @username</code> — username bo'yicha", parse_mode="HTML")
        return
        
    target = args[1].strip()
    target_id = None
    
    if target.startswith('@'):
        user_data = await UserDB.get_user_by_username(target)
        if not user_data:
            await message.answer(f"❌ <code>{target}</code> topilmadi.", parse_mode="HTML")
            return
        target_id = user_data.user_id
    elif target.isdigit():
        target_id = int(target)
    else:
        await message.answer("❌ Noto'g'ri format. ID raqam bo'lishi kerak.")
        return
        
    if target_id == ADMIN_ID:
        await message.answer("ℹ️ Siz asosiy adminsiz, huquq berishning hojati yo'q.")
        return
        
    success = await PrivilegedDB.add_privileged(target_id, message.from_user.id)
    if success:
        user_data = await UserDB.get_user(target_id)
        name = "Nomaʼlum"
        if user_data:
            name = user_data.first_name if user_data.first_name else ""
            if user_data.username:
                name += f" (@{user_data.username})"
        
        text = (
            f"✅ <b>{name}</b> ga ko'rish huquqi berildi!\n"
            f"🆔 ID: <code>{target_id}</code>\n\n"
            "Endi u o'ziga kelgan anonim xabarlarning yuboruvchisini ko'ra oladi."
        )
        await message.answer(text, parse_mode="HTML")
    else:
        await message.answer("❌ Xatolik yuz berdi.")

@router.message(Command("revoke"))
async def cmd_revoke(message: Message):
    if not await is_main_admin(message.from_user.id):
        return
        
    args = message.text.split(maxsplit=1)
    if len(args) < 2:
        await message.answer("📋 <b>Foydalanish:</b>\n<code>/revoke 123456789</code> — ID bo'yicha\n<code>/revoke @username</code> — username bo'yicha", parse_mode="HTML")
        return
        
    target = args[1].strip()
    target_id = None
    
    if target.startswith('@'):
        user_data = await UserDB.get_user_by_username(target)
        if not user_data:
            await message.answer(f"❌ <code>{target}</code> topilmadi.", parse_mode="HTML")
            return
        target_id = user_data.user_id
    elif target.isdigit():
        target_id = int(target)
    else:
        await message.answer("❌ Noto'g'ri format. ID raqam bo'lishi kerak.")
        return
        
    success = await PrivilegedDB.remove_privileged(target_id)
    if success:
        await message.answer(f"✅ <code>{target_id}</code> dan ko'rish huquqi olib tashlandi.", parse_mode="HTML")
    else:
        await message.answer("❌ Bu foydalanuvchi ishonchli insonlar ro'yxatida topilmadi.")

@router.message(Command("admins"))
async def cmd_admins(message: Message):
    if not await is_main_admin(message.from_user.id):
        return
        
    privileged_users = await PrivilegedDB.get_all_privileged()
    text = f"🛡️ <b>Ishonchli insonlar ro'yxati:</b>\n\n👑 <b>Asosiy admin:</b> <code>{ADMIN_ID}</code>\n\n"
    
    if privileged_users:
        for idx, (priv, user) in enumerate(privileged_users, 1):
            name = user.first_name if user and user.first_name else "Nomaʼlum"
            username = f"@{user.username}" if user and user.username else "username yo'q"
            text += f"{idx}. {name} ({username})\n   🆔 <code>{priv.user_id}</code> | 📋 {priv.role}\n\n"
    else:
        text += "📭 Hozircha ishonchli inson qo'shilmagan.\n\n💡 Qo'shish: <code>/grant user_id</code>"
        
    await message.answer(text, parse_mode="HTML")

@router.message(Command("block"))
async def cmd_block(message: Message):
    if not await PrivilegedDB.is_privileged(message.from_user.id):
        return
        
    args = message.text.split(maxsplit=1)
    if len(args) < 2:
        await message.answer("📋 <b>Foydalanish:</b> <code>/block user_id</code>", parse_mode="HTML")
        return
        
    target_id = args[1].strip()
    if not target_id.isdigit():
        await message.answer("❌ Noto'g'ri format. ID raqam bo'lishi kerak.")
        return
        
    target_id = int(target_id)
    if target_id == ADMIN_ID:
        await message.answer("❌ Asosiy adminni bloklash mumkin emas.")
        return
        
    success = await UserDB.block_user(target_id)
    if success:
        await message.answer(f"🚫 <code>{target_id}</code> bloklandi.\nBu foydalanuvchi endi anonim xabar yubora olmaydi.", parse_mode="HTML")
    else:
        await message.answer("❌ Foydalanuvchi topilmadi.")

@router.message(Command("unblock"))
async def cmd_unblock(message: Message):
    if not await PrivilegedDB.is_privileged(message.from_user.id):
        return
        
    args = message.text.split(maxsplit=1)
    if len(args) < 2:
        await message.answer("📋 <b>Foydalanish:</b> <code>/unblock user_id</code>", parse_mode="HTML")
        return
        
    target_id = args[1].strip()
    if not target_id.isdigit():
        await message.answer("❌ Noto'g'ri format. ID raqam bo'lishi kerak.")
        return
        
    target_id = int(target_id)
    success = await UserDB.unblock_user(target_id)
    if success:
        await message.answer(f"✅ <code>{target_id}</code> blokdan chiqarildi.", parse_mode="HTML")
    else:
        await message.answer("❌ Foydalanuvchi topilmadi.")

@router.message(Command("stats"))
async def cmd_stats(message: Message):
    if not await PrivilegedDB.is_privileged(message.from_user.id):
        return
        
    user_count = await UserDB.get_user_count()
    msg_count = await MessageDB.get_message_count()
    today_count = await MessageDB.get_today_message_count()
    privileged = await PrivilegedDB.get_all_privileged()
    
    text = (
        "📊 <b>Bot statistikasi</b>\n\n"
        f"👥 Jami foydalanuvchilar: <b>{user_count}</b>\n"
        f"📩 Jami xabarlar: <b>{msg_count}</b>\n"
        f"📅 Bugungi xabarlar: <b>{today_count}</b>\n"
        f"🛡️ Ishonchli insonlar: <b>{len(privileged)}</b>"
    )
    await message.answer(text, parse_mode="HTML")

@router.message(Command("broadcast"))
async def cmd_broadcast(message: Message):
    if not await is_main_admin(message.from_user.id):
        return
        
    args = message.text.split(maxsplit=1)
    if len(args) < 2:
        await message.answer("📋 <b>Foydalanish:</b> <code>/broadcast Xabar matni</code>", parse_mode="HTML")
        return
        
    broadcast_text = args[1].strip()
    users = await UserDB.get_all_users()
    
    status_msg = await message.answer(f"📤 Xabar yuborilmoqda... 0/{len(users)}")
    
    sent = 0
    failed = 0
    for user in users:
        try:
            await message.bot.send_message(user.user_id, f"📢 <b>Bot xabarnomasi:</b>\n\n{broadcast_text}", parse_mode="HTML")
            sent += 1
        except Exception:
            failed += 1
            
    text = (
        "✅ <b>Broadcast yakunlandi!</b>\n\n"
        f"📤 Yuborildi: <b>{sent}</b>\n"
        f"❌ Xatolik: <b>{failed}</b>\n"
        f"👥 Jami: <b>{len(users)}</b>"
    )
    await status_msg.edit_text(text, parse_mode="HTML")
