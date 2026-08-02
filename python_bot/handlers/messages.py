from aiogram import Router, F
from aiogram.types import Message
from database.crud import UserDB, ActiveSessionDB, PrivilegedDB, MessageDB
from utils.helpers import format_sender_info, get_message_type

router = Router()

async def send_anonymous_message(message: Message, receiver_id: int, sender_id: int) -> int:
    is_receiver_privileged = await PrivilegedDB.is_privileged(receiver_id)
    sender_data = await UserDB.get_user(sender_id)
    
    sender_info = ""
    if is_receiver_privileged and sender_data:
        sender_info = format_sender_info(sender_data)
        
    msg_type = get_message_type(message)
    bot = message.bot
    bot_msg = None
    
    try:
        if msg_type == 'text':
            text = f"📩 <b>Anonim xabar:</b>\n\n{message.text}"
            if sender_info:
                text += f"\n\n{sender_info}"
            bot_msg = await bot.send_message(receiver_id, text, parse_mode="HTML")
            
        elif msg_type == 'photo':
            photo = message.photo[-1]
            caption = "📩 <b>Anonim xabar:</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_photo(receiver_id, photo.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'video':
            caption = "📩 <b>Anonim xabar:</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_video(receiver_id, message.video.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'voice':
            caption = "📩 <b>Anonim ovozli xabar</b>"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_voice(receiver_id, message.voice.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'audio':
            caption = "📩 <b>Anonim audio</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_audio(receiver_id, message.audio.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'document':
            caption = "📩 <b>Anonim fayl</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_document(receiver_id, message.document.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'sticker':
            bot_msg = await bot.send_sticker(receiver_id, message.sticker.file_id)
            info_text = "📩 <b>Anonim sticker yuborildi</b>"
            if sender_info:
                info_text += f"\n\n{sender_info}"
            await bot.send_message(receiver_id, info_text, parse_mode="HTML", reply_to_message_id=bot_msg.message_id)
            
        elif msg_type == 'video_note':
            bot_msg = await bot.send_video_note(receiver_id, message.video_note.file_id)
            if sender_info:
                await bot.send_message(receiver_id, f"📩 <b>Anonim video xabar</b>\n\n{sender_info}", parse_mode="HTML", reply_to_message_id=bot_msg.message_id)
                
        elif msg_type == 'animation':
            caption = "📩 <b>Anonim GIF</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if sender_info:
                caption += f"\n\n{sender_info}"
            bot_msg = await bot.send_animation(receiver_id, message.animation.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'contact':
            bot_msg = await bot.send_contact(receiver_id, phone_number=message.contact.phone_number, first_name="Anonim")
            
        elif msg_type == 'location':
            bot_msg = await bot.send_location(receiver_id, latitude=message.location.latitude, longitude=message.location.longitude)
            
        else:
            text = f"📩 <b>Anonim xabar:</b>\n\n{message.text or '(bo\\'sh xabar)'}"
            if sender_info:
                text += f"\n\n{sender_info}"
            bot_msg = await bot.send_message(receiver_id, text, parse_mode="HTML")
            
        return bot_msg.message_id if bot_msg else None
    except Exception as e:
        print(f"Xabar yuborishda xatolik: {e}")
        return None

# Avval ReplyHandler (chunki u ham oddiy xabar bo'lishi mumkin)
@router.message(F.reply_to_message)
async def handle_reply(message: Message):
    user_id = message.from_user.id
    replied_msg_id = message.reply_to_message.message_id
    
    sender_id = await MessageDB.get_sender_by_bot_message(user_id, replied_msg_id)
    if not sender_id:
        # Bu botning boshqa turdagi xabari bo'lishi mumkin, anonim xabar sessiyasiga o'tkazib yuboramiz
        return await handle_anonymous_message(message)
        
    is_sender_privileged = await PrivilegedDB.is_privileged(sender_id)
    replier_data = await UserDB.get_user(user_id)
    
    replier_info = ""
    if is_sender_privileged and replier_data:
        replier_info = format_sender_info(replier_data)
        
    msg_type = get_message_type(message)
    bot = message.bot
    bot_msg = None
    
    try:
        if msg_type == 'text':
            text = f"💬 <b>Anonim xabaringizga javob:</b>\n\n{message.text}"
            if replier_info:
                text += f"\n\n{replier_info}"
            bot_msg = await bot.send_message(sender_id, text, parse_mode="HTML")
            
        elif msg_type == 'photo':
            photo = message.photo[-1]
            caption = "💬 <b>Anonim xabaringizga javob:</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_photo(sender_id, photo.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'video':
            caption = "💬 <b>Anonim xabaringizga javob:</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_video(sender_id, message.video.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'voice':
            caption = "💬 <b>Anonim xabaringizga javob (ovozli)</b>"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_voice(sender_id, message.voice.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'audio':
            caption = "💬 <b>Anonim xabaringizga javob (audio)</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_audio(sender_id, message.audio.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'document':
            caption = "💬 <b>Anonim xabaringizga javob (fayl)</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_document(sender_id, message.document.file_id, caption=caption, parse_mode="HTML")
            
        elif msg_type == 'sticker':
            bot_msg = await bot.send_sticker(sender_id, message.sticker.file_id)
            info_text = "💬 <b>Anonim xabaringizga sticker bilan javob berildi</b>"
            if replier_info:
                info_text += f"\n\n{replier_info}"
            await bot.send_message(sender_id, info_text, parse_mode="HTML", reply_to_message_id=bot_msg.message_id)
            
        elif msg_type == 'video_note':
            bot_msg = await bot.send_video_note(sender_id, message.video_note.file_id)
            if replier_info:
                await bot.send_message(sender_id, f"💬 <b>Video xabarga javob</b>\n\n{replier_info}", parse_mode="HTML", reply_to_message_id=bot_msg.message_id)
                
        elif msg_type == 'animation':
            caption = "💬 <b>Anonim xabaringizga javob (GIF)</b>"
            if message.caption:
                caption += f"\n\n{message.caption}"
            if replier_info:
                caption += f"\n\n{replier_info}"
            bot_msg = await bot.send_animation(sender_id, message.animation.file_id, caption=caption, parse_mode="HTML")
            
        else:
            text = f"💬 <b>Anonim xabaringizga javob:</b>\n\n{message.text or '(bo\\'sh)'}"
            if replier_info:
                text += f"\n\n{replier_info}"
            bot_msg = await bot.send_message(sender_id, text, parse_mode="HTML")
            
        if bot_msg:
            await MessageDB.save_message(user_id, sender_id, bot_msg.message_id, msg_type)
            await message.answer("✅ Javobingiz anonim yuborildi! 👻")
        else:
            await message.answer("❌ Javobni yuborib bo'lmadi.")
    except Exception as e:
        print(f"Javob yuborishda xatolik: {e}")
        await message.answer("❌ Javobni yuborib bo'lmadi.\nFoydalanuvchi botni bloklagan bo'lishi mumkin.")


# Regular messages (anonymous)
@router.message()
async def handle_anonymous_message(message: Message):
    if not message.text and not get_message_type(message):
        return
        
    # Command bo'lsa o'tkazib yubormaymiz (agar boshqa handlerga tushmagan bo'lsa)
    if message.text and message.text.startswith('/'):
        return
        
    sender_id = message.from_user.id
    receiver_id = await ActiveSessionDB.get_session(sender_id)
    
    if not receiver_id:
        await message.answer("🤔 Anonim xabar yuborish uchun biror kishining havolasini bosing.\n\n🏠 Bosh menyu: /start\n🔗 Havolangiz: /mylink")
        return
        
    is_blocked = await UserDB.is_blocked(sender_id)
    if is_blocked:
        await message.answer("🚫 Siz bloklangansiz va xabar yubora olmaysiz.")
        await ActiveSessionDB.delete_session(sender_id)
        return
        
    bot_message_id = await send_anonymous_message(message, receiver_id, sender_id)
    
    if bot_message_id:
        msg_type = get_message_type(message)
        await MessageDB.save_message(sender_id, receiver_id, bot_message_id, msg_type)
        await message.answer("✅ Xabaringiz anonim yuborildi! 👻\n\n📝 Yana xabar yozishingiz mumkin.\n🔙 Bekor qilish: /cancel\n🏠 Bosh menyu: /start")
    else:
        await message.answer("❌ Xabarni yuborib bo'lmadi.\nFoydalanuvchi botni bloklagan bo'lishi mumkin.")
