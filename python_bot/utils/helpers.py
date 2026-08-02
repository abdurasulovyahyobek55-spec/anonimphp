import random
import string

def generate_unique_code(length=8):
    chars = string.ascii_letters + string.digits
    return ''.join(random.choice(chars) for _ in range(length))

def format_sender_info(user_data):
    name_parts = []
    if user_data.first_name:
        name_parts.append(user_data.first_name)
    if user_data.last_name:
        name_parts.append(user_data.last_name)
    full_name = " ".join(name_parts) if name_parts else "Nomaʼlum"
    
    username_str = f"@{user_data.username}" if user_data.username else "yo'q"
    user_id = user_data.user_id if user_data.user_id else "nomaʼlum"
    
    if user_data.username:
        profile_link = f"<a href='https://t.me/{user_data.username}'>🔗 Profilni ochish</a>"
    else:
        profile_link = f"<a href='tg://user?id={user_id}'>🔗 Profilni ochish</a>"
        
    return (
        "\n╔══════════════════════╗\n"
        "║  🔍 YUBORUVCHI MA'LUMOTI\n"
        "╠══════════════════════╣\n"
        f"║  👤 Ism: {full_name}\n"
        f"║  📎 Username: {username_str}\n"
        f"║  🆔 ID: <code>{user_id}</code>\n"
        f"║  🔗 Profil: {profile_link}\n"
        "╚══════════════════════╝"
    )

def get_message_type(message):
    if message.photo: return 'photo'
    if message.video: return 'video'
    if message.voice: return 'voice'
    if message.audio: return 'audio'
    if message.document: return 'document'
    if message.sticker: return 'sticker'
    if message.video_note: return 'video_note'
    if message.animation: return 'animation'
    if message.contact: return 'contact'
    if message.location: return 'location'
    return 'text'
