"""
Data cleaning and transformation script for Baseball Analytics System
This script cleans and transforms the collected baseball data for analysis.
"""

import os
import sys
import pandas as pd
import numpy as np
from datetime import datetime
import sqlalchemy as sa
from sqlalchemy import create_engine, text
import logging
import glob

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("logs/data_cleaning.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Database connection settings
DB_USER = "postgres"
DB_PASSWORD = "baseball_analytics"
DB_HOST = "localhost"
DB_PORT = "5432"
DB_NAME = "baseball_analytics"

# Create the database URL
DATABASE_URL = f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"

# Create data directories if they don't exist
os.makedirs('data/processed', exist_ok=True)
os.makedirs('logs', exist_ok=True)

def connect_to_db():
    """Connect to the database"""
    try:
        engine = create_engine(DATABASE_URL)
        return engine
    except Exception as e:
        logger.error(f"Error connecting to database: {e}")
        sys.exit(1)

def clean_statcast_data(file_path):
    """
    Clean and transform Statcast data
    
    Args:
        file_path (str): Path to the Statcast data CSV file
        
    Returns:
        pandas.DataFrame: Cleaned Statcast data
    """
    try:
        logger.info(f"Cleaning Statcast data from {file_path}")
        
        # Read the data
        data = pd.read_csv(file_path)
        
        # Basic cleaning
        # Replace empty strings with NaN
        data = data.replace('', np.nan)
        
        # Convert date columns to datetime
        if 'game_date' in data.columns:
            data['game_date'] = pd.to_datetime(data['game_date'], errors='coerce')
        
        # Handle numeric columns
        numeric_columns = [
            'release_speed', 'release_pos_x', 'release_pos_z', 'plate_x', 'plate_z',
            'launch_speed', 'launch_angle', 'hit_distance_sc', 'release_spin_rate'
        ]
        
        for col in numeric_columns:
            if col in data.columns:
                data[col] = pd.to_numeric(data[col], errors='coerce')
        
        # Drop rows with critical missing values
        critical_columns = ['pitch_type', 'game_date', 'player_name']
        critical_columns = [col for col in critical_columns if col in data.columns]
        if critical_columns:
            data = data.dropna(subset=critical_columns)
        
        # Create derived features
        if all(col in data.columns for col in ['launch_speed', 'launch_angle']):
            # Create a binary column for hard-hit balls (exit velocity >= 95 mph)
            data['hard_hit'] = (data['launch_speed'] >= 95).astype(int)
            
            # Create a binary column for barrels (optimal launch angle and speed)
            data['barrel'] = ((data['launch_speed'] >= 98) & 
                             (data['launch_angle'] >= 26) & 
                             (data['launch_angle'] <= 30)).astype(int)
        
        logger.info(f"Cleaned Statcast data: {len(data)} records")
        return data
    
    except Exception as e:
        logger.error(f"Error cleaning Statcast data: {e}")
        return pd.DataFrame()

def clean_batting_stats(file_path):
    """
    Clean and transform batting statistics
    
    Args:
        file_path (str): Path to the batting stats CSV file
        
    Returns:
        pandas.DataFrame: Cleaned batting statistics
    """
    try:
        logger.info(f"Cleaning batting stats from {file_path}")
        
        # Read the data
        data = pd.read_csv(file_path)
        
        # Basic cleaning
        # Replace empty strings with NaN
        data = data.replace('', np.nan)
        
        # Extract season from filename if not in data
        if 'Season' not in data.columns:
            # Extract year from filename (e.g., batting_stats_2023.csv)
            year = os.path.basename(file_path).split('_')[-1].split('.')[0]
            data['Season'] = year
        
        # Handle numeric columns
        numeric_columns = [
            'G', 'AB', 'PA', 'H', '1B', '2B', '3B', 'HR', 'R', 'RBI', 
            'BB', 'IBB', 'SO', 'HBP', 'SF', 'SH', 'GDP', 'SB', 'CS',
            'AVG', 'OBP', 'SLG', 'OPS', 'wOBA', 'wRC+', 'WAR'
        ]
        
        for col in numeric_columns:
            if col in data.columns:
                data[col] = pd.to_numeric(data[col], errors='coerce')
        
        # Create derived features
        if all(col in data.columns for col in ['BB', 'AB', 'HBP', 'SF']):
            # Calculate walk rate (BB/PA)
            data['BB%'] = data['BB'] / (data['AB'] + data['BB'] + data['HBP'] + data['SF'])
        
        if all(col in data.columns for col in ['SO', 'AB', 'BB', 'HBP', 'SF']):
            # Calculate strikeout rate (SO/PA)
            data['K%'] = data['SO'] / (data['AB'] + data['BB'] + data['HBP'] + data['SF'])
        
        if all(col in data.columns for col in ['HR', 'AB']):
            # Calculate home run rate (HR/AB)
            data['HR%'] = data['HR'] / data['AB']
        
        # Fill NaN values in calculated columns with 0
        if 'BB%' in data.columns:
            data['BB%'] = data['BB%'].fillna(0)
        if 'K%' in data.columns:
            data['K%'] = data['K%'].fillna(0)
        if 'HR%' in data.columns:
            data['HR%'] = data['HR%'].fillna(0)
        
        logger.info(f"Cleaned batting stats: {len(data)} records")
        return data
    
    except Exception as e:
        logger.error(f"Error cleaning batting stats: {e}")
        return pd.DataFrame()

def clean_pitching_stats(file_path):
    """
    Clean and transform pitching statistics
    
    Args:
        file_path (str): Path to the pitching stats CSV file
        
    Returns:
        pandas.DataFrame: Cleaned pitching statistics
    """
    try:
        logger.info(f"Cleaning pitching stats from {file_path}")
        
        # Read the data
        data = pd.read_csv(file_path)
        
        # Basic cleaning
        # Replace empty strings with NaN
        data = data.replace('', np.nan)
        
        # Extract season from filename if not in data
        if 'Season' not in data.columns:
            # Extract year from filename (e.g., pitching_stats_2023.csv)
            year = os.path.basename(file_path).split('_')[-1].split('.')[0]
            data['Season'] = year
        
        # Handle numeric columns
        numeric_columns = [
            'W', 'L', 'G', 'GS', 'CG', 'ShO', 'SV', 'BS', 'IP', 'TBF',
            'H', 'R', 'ER', 'HR', 'BB', 'IBB', 'HBP', 'WP', 'BK', 'SO',
            'ERA', 'FIP', 'xFIP', 'WHIP', 'BABIP', 'K/9', 'BB/9', 'K/BB', 'WAR'
        ]
        
        for col in numeric_columns:
            if col in data.columns:
                data[col] = pd.to_numeric(data[col], errors='coerce')
        
        # Create derived features
        if all(col in data.columns for col in ['BB', 'IP']):
            # Calculate walks per 9 innings if not already present
            if 'BB/9' not in data.columns:
                data['BB/9'] = (data['BB'] * 9) / data['IP']
        
        if all(col in data.columns for col in ['SO', 'IP']):
            # Calculate strikeouts per 9 innings if not already present
            if 'K/9' not in data.columns:
                data['K/9'] = (data['SO'] * 9) / data['IP']
        
        if all(col in data.columns for col in ['HR', 'IP']):
            # Calculate home runs per 9 innings
            data['HR/9'] = (data['HR'] * 9) / data['IP']
        
        # Fill NaN values in calculated columns with 0
        if 'BB/9' in data.columns:
            data['BB/9'] = data['BB/9'].fillna(0)
        if 'K/9' in data.columns:
            data['K/9'] = data['K/9'].fillna(0)
        if 'HR/9' in data.columns:
            data['HR/9'] = data['HR/9'].fillna(0)
        
        logger.info(f"Cleaned pitching stats: {len(data)} records")
        return data
    
    except Exception as e:
        logger.error(f"Error cleaning pitching stats: {e}")
        return pd.DataFrame()

def clean_team_data(file_path):
    """
    Clean and transform team data
    
    Args:
        file_path (str): Path to the team data CSV file
        
    Returns:
        pandas.DataFrame: Cleaned team data
    """
    try:
        logger.info(f"Cleaning team data from {file_path}")
        
        # Read the data
        data = pd.read_csv(file_path)
        
        # Basic cleaning
        # Replace empty strings with NaN
        data = data.replace('', np.nan)
        
        # Extract season from filename if not in data
        if 'Season' not in data.columns:
            # Extract year from filename (e.g., team_data_2023.csv)
            year = os.path.basename(file_path).split('_')[-1].split('.')[0]
            data['Season'] = year
        
        # Handle numeric columns
        numeric_columns = [
            'G', 'PA', 'HR', 'R', 'RBI', 'SB', 'BB%', 'K%', 
            'ISO', 'BABIP', 'AVG', 'OBP', 'SLG', 'wOBA', 'wRC+', 'BsR', 'Off', 'Def', 'WAR'
        ]
        
        for col in numeric_columns:
            if col in data.columns:
                data[col] = pd.to_numeric(data[col], errors='coerce')
        
        logger.info(f"Cleaned team data: {len(data)} records")
        return data
    
    except Exception as e:
        logger.error(f"Error cleaning team data: {e}")
        return pd.DataFrame()

def save_to_csv(data, filename, directory='processed'):
    """
    Save data to CSV file
    
    Args:
        data (pandas.DataFrame): Data to save
        filename (str): Filename to save to
        directory (str): Directory to save to (default: 'processed')
    """
    try:
        filepath = os.path.join(f'data/{directory}', filename)
        data.to_csv(filepath, index=False)
        logger.info(f"Saved data to {filepath}")
    except Exception as e:
        logger.error(f"Error saving data to {filename}: {e}")

def save_to_database(data, table_name, engine, if_exists='append'):
    """
    Save data to database
    
    Args:
        data (pandas.DataFrame): Data to save
        table_name (str): Table name to save to
        engine (sqlalchemy.engine.Engine): Database engine
        if_exists (str): How to behave if the table already exists
    """
    try:
        data.to_sql(table_name, engine, if_exists=if_exists, index=False)
        logger.info(f"Saved {len(data)} records to {table_name} table")
    except Exception as e:
        logger.error(f"Error saving data to {table_name} table: {e}")

def validate_data(data, validation_rules):
    """
    Validate data against a set of rules
    
    Args:
        data (pandas.DataFrame): Data to validate
        validation_rules (dict): Dictionary of validation rules
        
    Returns:
        tuple: (valid_data, invalid_data, validation_report)
    """
    validation_report = {}
    invalid_rows = []
    
    for rule_name, rule_func in validation_rules.items():
        try:
            # Apply the validation rule
            valid_mask = rule_func(data)
            invalid_mask = ~valid_mask
            
            # Count invalid rows
            invalid_count = invalid_mask.sum()
            
            # Add to report
            validation_report[rule_name] = {
                'total_rows': len(data),
                'valid_rows': len(data) - invalid_count,
                'invalid_rows': invalid_count,
                'invalid_percentage': (invalid_count / len(data)) * 100 if len(data) > 0 else 0
            }
            
            # Track invalid rows
            if invalid_count > 0:
                invalid_rows.append(invalid_mask)
        
        except Exception as e:
            logger.error(f"Error applying validation rule {rule_name}: {e}")
            validation_report[rule_name] = {'error': str(e)}
    
    # Combine all invalid rows
    if invalid_rows:
        combined_invalid_mask = pd.concat(invalid_rows, axis=1).any(axis=1)
        invalid_data = data[combined_invalid_mask].copy()
        valid_data = data[~combined_invalid_mask].copy()
    else:
        invalid_data = pd.DataFrame()
        valid_data = data.copy()
    
    return valid_data, invalid_data, validation_report

def process_all_data():
    """Process all collected data files"""
    # Connect to database
    engine = connect_to_db()
    
    # Process Statcast data
    statcast_files = glob.glob('data/raw/statcast_*.csv')
    for file_path in statcast_files:
        clean_data = clean_statcast_data(file_path)
        if not clean_data.empty:
            output_filename = os.path.basename(file_path).replace('statcast_', 'clean_statcast_')
            save_to_csv(clean_data, output_filename)
            # Note: Database saving would require mapping to the schema
    
    # Process batting stats
    batting_files = glob.glob('data/raw/batting_stats_*.csv')
    for file_path in batting_files:
        clean_data = clean_batting_stats(file_path)
        if not clean_data.empty:
            output_filename = os.path.basename(file_path).replace('batting_stats_', 'clean_batting_stats_')
            save_to_csv(clean_data, output_filename)
            # Note: Database saving would require mapping to the schema
    
    # Process pitching stats
    pitching_files = glob.glob('data/raw/pitching_stats_*.csv')
    for file_path in pitching_files:
        clean_data = clean_pitching_stats(file_path)
        if not clean_data.empty:
            output_filename = os.path.basename(file_path).replace('pitching_stats_', 'clean_pitching_stats_')
            save_to_csv(clean_data, output_filename)
            # Note: Database saving would require mapping to the schema
    
    # Process team data
    team_files = glob.glob('data/raw/team_data_*.csv')
    for file_path in team_files:
        clean_data = clean_team_data(file_path)
        if not clean_data.empty:
            output_filename = os.path.basename(file_path).replace('team_data_', 'clean_team_data_')
            save_to_csv(clean_data, output_filename)
            # Note: Database saving would require mapping to the schema

def main():
    """Main function to clean and transform baseball data"""
    try:
        # Create log directory if it doesn't exist
        os.makedirs('logs', exist_ok=True)
        
        # Process all data
        logger.info("Starting data cleaning and transformation")
        process_all_data()
        logger.info("Data cleaning and transformation completed")
        
    except Exception as e:
        logger.error(f"Error in data cleaning: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
