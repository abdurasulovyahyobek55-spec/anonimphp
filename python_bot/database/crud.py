import os
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker
from sqlalchemy import select, func, update, delete
from sqlalchemy.exc import IntegrityError
from .models import Base, User, PrivilegedUser, Message, ActiveSession
from config import DATABASE_URL, ADMIN_ID
from datetime import datetime, date

# Ma'lumotlar bazasi faylini yaratish uchun data jildini tekshiramiz
if DATABASE_URL.startswith("sqlite"):
    os.makedirs(os.path.dirname("data/anonim.db"), exist_ok=True)

engine = create_async_engine(DATABASE_URL, echo=False)
async_session = async_sessionmaker(engine, expire_on_commit=False)

async def init_db():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

class UserDB:
    @staticmethod
    async def save_user(user_id: int, username: str = None, first_name: str = None, last_name: str = None, unique_code: str = None):
        async with async_session() as session:
            result = await session.execute(select(User).where(User.user_id == user_id))
            user = result.scalar_one_or_none()
            if user:
                user.username = username
                user.first_name = first_name
                user.last_name = last_name
            else:
                user = User(user_id=user_id, username=username, first_name=first_name, last_name=last_name, unique_code=unique_code)
                session.add(user)
            await session.commit()
    
    @staticmethod
    async def get_user(user_id: int):
        async with async_session() as session:
            result = await session.execute(select(User).where(User.user_id == user_id))
            return result.scalar_one_or_none()
    
    @staticmethod
    async def get_user_by_code(code: str):
        async with async_session() as session:
            result = await session.execute(select(User).where(User.unique_code == code))
            return result.scalar_one_or_none()
            
    @staticmethod
    async def get_user_by_username(username: str):
        async with async_session() as session:
            cleaned_username = username.lstrip('@')
            result = await session.execute(select(User).where(User.username == cleaned_username))
            return result.scalar_one_or_none()
            
    @staticmethod
    async def is_blocked(user_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(select(User.is_blocked).where(User.user_id == user_id))
            val = result.scalar_one_or_none()
            return bool(val)

    @staticmethod
    async def block_user(user_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(update(User).where(User.user_id == user_id).values(is_blocked=True))
            await session.commit()
            return result.rowcount > 0

    @staticmethod
    async def unblock_user(user_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(update(User).where(User.user_id == user_id).values(is_blocked=False))
            await session.commit()
            return result.rowcount > 0

    @staticmethod
    async def get_all_users():
        async with async_session() as session:
            result = await session.execute(select(User))
            return result.scalars().all()

    @staticmethod
    async def get_user_count() -> int:
        async with async_session() as session:
            result = await session.execute(select(func.count(User.user_id)))
            return result.scalar()

class PrivilegedDB:
    @staticmethod
    async def is_privileged(user_id: int) -> bool:
        if user_id == ADMIN_ID:
            return True
        async with async_session() as session:
            result = await session.execute(select(PrivilegedUser).where(PrivilegedUser.user_id == user_id))
            return result.scalar_one_or_none() is not None

    @staticmethod
    async def add_privileged(user_id: int, granted_by: int, role: str = "trusted") -> bool:
        async with async_session() as session:
            try:
                result = await session.execute(select(PrivilegedUser).where(PrivilegedUser.user_id == user_id))
                priv = result.scalar_one_or_none()
                if priv:
                    priv.role = role
                    priv.granted_by = granted_by
                else:
                    priv = PrivilegedUser(user_id=user_id, role=role, granted_by=granted_by)
                    session.add(priv)
                await session.commit()
                return True
            except Exception:
                return False

    @staticmethod
    async def remove_privileged(user_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(delete(PrivilegedUser).where(PrivilegedUser.user_id == user_id))
            await session.commit()
            return result.rowcount > 0

    @staticmethod
    async def get_all_privileged():
        async with async_session() as session:
            stmt = select(PrivilegedUser, User).outerjoin(User, PrivilegedUser.user_id == User.user_id)
            result = await session.execute(stmt)
            # Returns a list of tuples (PrivilegedUser, User)
            return result.all()

class MessageDB:
    @staticmethod
    async def save_message(sender_id: int, receiver_id: int, bot_message_id: int, message_type: str = "text") -> int:
        async with async_session() as session:
            msg = Message(sender_id=sender_id, receiver_id=receiver_id, bot_message_id=bot_message_id, message_type=message_type)
            session.add(msg)
            await session.commit()
            return msg.id

    @staticmethod
    async def get_sender_by_bot_message(receiver_id: int, bot_message_id: int) -> int:
        async with async_session() as session:
            stmt = select(Message.sender_id).where(
                Message.receiver_id == receiver_id, 
                Message.bot_message_id == bot_message_id
            ).order_by(Message.created_at.desc()).limit(1)
            result = await session.execute(stmt)
            return result.scalar_one_or_none()

    @staticmethod
    async def get_message_count() -> int:
        async with async_session() as session:
            result = await session.execute(select(func.count(Message.id)))
            return result.scalar()

    @staticmethod
    async def get_today_message_count() -> int:
        async with async_session() as session:
            today = date.today()
            # This works for both Postgres and SQLite usually, but simpler is comparing created_at >= today
            result = await session.execute(select(func.count(Message.id)).where(Message.created_at >= today))
            return result.scalar()

class ActiveSessionDB:
    @staticmethod
    async def set_session(user_id: int, receiver_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(select(ActiveSession).where(ActiveSession.user_id == user_id))
            sess = result.scalar_one_or_none()
            if sess:
                sess.receiver_id = receiver_id
            else:
                sess = ActiveSession(user_id=user_id, receiver_id=receiver_id)
                session.add(sess)
            await session.commit()
            return True

    @staticmethod
    async def get_session(user_id: int) -> int:
        async with async_session() as session:
            result = await session.execute(select(ActiveSession.receiver_id).where(ActiveSession.user_id == user_id))
            return result.scalar_one_or_none()

    @staticmethod
    async def delete_session(user_id: int) -> bool:
        async with async_session() as session:
            result = await session.execute(delete(ActiveSession).where(ActiveSession.user_id == user_id))
            await session.commit()
            return result.rowcount > 0
