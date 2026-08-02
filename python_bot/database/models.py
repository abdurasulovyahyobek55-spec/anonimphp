from sqlalchemy import Column, Integer, String, Boolean, DateTime, func, BigInteger
from sqlalchemy.orm import declarative_base

Base = declarative_base()

class User(Base):
    __tablename__ = "users"
    
    user_id = Column(BigInteger, primary_key=True)
    username = Column(String, nullable=True)
    first_name = Column(String, nullable=True)
    last_name = Column(String, nullable=True)
    unique_code = Column(String, unique=True, nullable=True)
    is_blocked = Column(Boolean, default=False)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())

class PrivilegedUser(Base):
    __tablename__ = "privileged_users"
    
    user_id = Column(BigInteger, primary_key=True)
    role = Column(String, default="trusted")
    granted_by = Column(BigInteger, nullable=True)
    granted_at = Column(DateTime, server_default=func.now())

class Message(Base):
    __tablename__ = "messages"
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    sender_id = Column(BigInteger, index=True)
    receiver_id = Column(BigInteger, index=True)
    bot_message_id = Column(BigInteger, index=True)
    message_type = Column(String, default="text")
    created_at = Column(DateTime, server_default=func.now())

class ActiveSession(Base):
    __tablename__ = "active_sessions"
    
    user_id = Column(BigInteger, primary_key=True)
    receiver_id = Column(BigInteger, nullable=False)
    started_at = Column(DateTime, server_default=func.now())
