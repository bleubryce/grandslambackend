"""
Database setup script for Baseball Analytics System
This script creates the necessary database, schemas, and tables for the baseball analytics system.
"""

import os
import sys
import sqlalchemy as sa
from sqlalchemy import create_engine, MetaData, Table, Column, Integer, String, Float, Date, ForeignKey, Text, Boolean
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, relationship

# Database connection settings
DB_USER = "postgres"
DB_PASSWORD = "baseball_analytics"
DB_HOST = "localhost"
DB_PORT = "5432"
DB_NAME = "baseball_analytics"

# Create the database URL
DATABASE_URL = f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"

def create_database():
    """Create the database if it doesn't exist"""
    # Connect to the default postgres database to create our application database
    engine = create_engine(f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/postgres", 
                          isolation_level="AUTOCOMMIT")
    conn = engine.connect()
    
    # Check if our database exists
    result = conn.execute(sa.text(f"SELECT 1 FROM pg_database WHERE datname = '{DB_NAME}'"))
    if not result.fetchone():
        print(f"Creating database {DB_NAME}...")
        # Need to use text() for raw SQL execution
        conn.execute(sa.text(f"CREATE DATABASE {DB_NAME}"))
        print(f"Database {DB_NAME} created successfully!")
    else:
        print(f"Database {DB_NAME} already exists.")
    
    conn.close()
    engine.dispose()

def setup_schemas():
    """Set up the database schemas and tables"""
    # Connect to our application database
    engine = create_engine(DATABASE_URL)
    Base = declarative_base()
    
    # Define models
    class Player(Base):
        __tablename__ = 'players'
        
        id = Column(Integer, primary_key=True)
        mlbam_id = Column(Integer, unique=True)
        first_name = Column(String(100))
        last_name = Column(String(100))
        full_name = Column(String(200))
        position = Column(String(10))
        birth_date = Column(Date)
        bats = Column(String(5))
        throws = Column(String(5))
        debut_date = Column(Date)
        team_id = Column(Integer, ForeignKey('teams.id'))
        
        team = relationship("Team", back_populates="players")
        batting_stats = relationship("BattingStats", back_populates="player")
        pitching_stats = relationship("PitchingStats", back_populates="player")
    
    class Team(Base):
        __tablename__ = 'teams'
        
        id = Column(Integer, primary_key=True)
        mlbam_id = Column(Integer, unique=True)
        name = Column(String(100))
        short_name = Column(String(50))
        abbreviation = Column(String(10))
        location = Column(String(100))
        division = Column(String(50))
        league = Column(String(10))
        
        players = relationship("Player", back_populates="team")
    
    class BattingStats(Base):
        __tablename__ = 'batting_stats'
        
        id = Column(Integer, primary_key=True)
        player_id = Column(Integer, ForeignKey('players.id'))
        season = Column(Integer)
        team_id = Column(Integer, ForeignKey('teams.id'))
        games = Column(Integer)
        plate_appearances = Column(Integer)
        at_bats = Column(Integer)
        runs = Column(Integer)
        hits = Column(Integer)
        doubles = Column(Integer)
        triples = Column(Integer)
        home_runs = Column(Integer)
        runs_batted_in = Column(Integer)
        stolen_bases = Column(Integer)
        caught_stealing = Column(Integer)
        walks = Column(Integer)
        strikeouts = Column(Integer)
        batting_average = Column(Float)
        on_base_percentage = Column(Float)
        slugging_percentage = Column(Float)
        ops = Column(Float)
        woba = Column(Float)
        wrc_plus = Column(Integer)
        war = Column(Float)
        
        player = relationship("Player", back_populates="batting_stats")
    
    class PitchingStats(Base):
        __tablename__ = 'pitching_stats'
        
        id = Column(Integer, primary_key=True)
        player_id = Column(Integer, ForeignKey('players.id'))
        season = Column(Integer)
        team_id = Column(Integer, ForeignKey('teams.id'))
        games = Column(Integer)
        games_started = Column(Integer)
        wins = Column(Integer)
        losses = Column(Integer)
        saves = Column(Integer)
        innings_pitched = Column(Float)
        hits_allowed = Column(Integer)
        runs_allowed = Column(Integer)
        earned_runs = Column(Integer)
        home_runs_allowed = Column(Integer)
        walks = Column(Integer)
        strikeouts = Column(Integer)
        era = Column(Float)
        whip = Column(Float)
        fip = Column(Float)
        xfip = Column(Float)
        war = Column(Float)
        
        player = relationship("Player", back_populates="pitching_stats")
    
    class StatcastData(Base):
        __tablename__ = 'statcast_data'
        
        id = Column(Integer, primary_key=True)
        game_date = Column(Date)
        player_id = Column(Integer, ForeignKey('players.id'))
        pitcher_id = Column(Integer, ForeignKey('players.id'))
        batter_id = Column(Integer, ForeignKey('players.id'))
        pitch_type = Column(String(10))
        release_speed = Column(Float)
        release_pos_x = Column(Float)
        release_pos_z = Column(Float)
        plate_x = Column(Float)
        plate_z = Column(Float)
        launch_speed = Column(Float)
        launch_angle = Column(Float)
        hit_distance = Column(Float)
        spin_rate = Column(Float)
        spin_axis = Column(Float)
        pitch_name = Column(String(50))
        description = Column(String(100))
        events = Column(String(50))
    
    class ScoutingReport(Base):
        __tablename__ = 'scouting_reports'
        
        id = Column(Integer, primary_key=True)
        player_id = Column(Integer, ForeignKey('players.id'))
        report_date = Column(Date)
        scout_name = Column(String(100))
        overall_grade = Column(Float)
        report_text = Column(Text)
        future_value = Column(Float)
    
    # Create all tables
    print("Creating database tables...")
    Base.metadata.create_all(engine)
    print("Database tables created successfully!")
    
    engine.dispose()

def main():
    """Main function to set up the database"""
    try:
        create_database()
        setup_schemas()
        print("Database setup completed successfully!")
    except Exception as e:
        print(f"Error setting up database: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
