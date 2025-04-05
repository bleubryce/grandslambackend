"""
Interactive dashboard for Baseball Analytics System
This script creates an interactive dashboard for visualizing baseball data and insights.
"""

import os
import sys
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime
import logging
import glob
import joblib
import dash
from dash import dcc, html, Input, Output, State, dash_table
import plotly.express as px
import plotly.graph_objects as go
from plotly.subplots import make_subplots

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("logs/dashboard.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Create directories if they don't exist
os.makedirs('logs', exist_ok=True)

def load_data(data_type, year=None):
    """
    Load cleaned data for visualization
    
    Args:
        data_type (str): Type of data to load ('batting', 'pitching', 'team', 'statcast')
        year (int, optional): Specific year to load. If None, loads all years.
        
    Returns:
        pandas.DataFrame: Loaded data
    """
    try:
        if year:
            # Load specific year
            file_pattern = f'data/processed/clean_{data_type}_stats_{year}.csv'
            if data_type == 'statcast':
                # Statcast files have different naming pattern
                file_pattern = f'data/processed/clean_statcast_*_{year}*.csv'
            
            files = glob.glob(file_pattern)
            if not files:
                logger.warning(f"No {data_type} data files found for year {year}")
                return pd.DataFrame()
            
            data = pd.read_csv(files[0])
            logger.info(f"Loaded {data_type} data for {year}: {len(data)} records")
            
        else:
            # Load all years
            file_pattern = f'data/processed/clean_{data_type}_stats_*.csv'
            if data_type == 'statcast':
                file_pattern = 'data/processed/clean_statcast_*.csv'
            
            files = glob.glob(file_pattern)
            if not files:
                logger.warning(f"No {data_type} data files found")
                return pd.DataFrame()
            
            data_frames = []
            for file in files:
                df = pd.read_csv(file)
                data_frames.append(df)
            
            data = pd.concat(data_frames, ignore_index=True)
            logger.info(f"Loaded {data_type} data for all years: {len(data)} records")
        
        return data
    
    except Exception as e:
        logger.error(f"Error loading {data_type} data: {e}")
        return pd.DataFrame()

def load_model_results():
    """
    Load model results for visualization
    
    Returns:
        dict: Dictionary of model results
    """
    results = {}
    
    try:
        # Load batting model metrics
        batting_metrics_file = 'reports/batting_model_metrics.csv'
        if os.path.exists(batting_metrics_file):
            results['batting_metrics'] = pd.read_csv(batting_metrics_file)
        
        # Load batting feature importance
        batting_importance_file = 'reports/batting_feature_importance.csv'
        if os.path.exists(batting_importance_file):
            results['batting_importance'] = pd.read_csv(batting_importance_file)
        
        # Load pitching model metrics
        pitching_metrics_file = 'reports/pitching_model_metrics.csv'
        if os.path.exists(pitching_metrics_file):
            results['pitching_metrics'] = pd.read_csv(pitching_metrics_file)
        
        # Load pitching feature importance
        pitching_importance_file = 'reports/pitching_feature_importance.csv'
        if os.path.exists(pitching_importance_file):
            results['pitching_importance'] = pd.read_csv(pitching_importance_file)
        
        logger.info("Loaded model results successfully")
        return results
    
    except Exception as e:
        logger.error(f"Error loading model results: {e}")
        return results

def load_player_comparisons():
    """
    Load player comparison results
    
    Returns:
        dict: Dictionary of player comparison results
    """
    comparisons = {}
    
    try:
        # Find all player comparison files
        batter_files = glob.glob('reports/similar_batters_*.csv')
        pitcher_files = glob.glob('reports/similar_pitchers_*.csv')
        
        # Load batter comparisons
        for file in batter_files:
            player_name = os.path.basename(file).replace('similar_batters_to_', '').replace('.csv', '').replace('_', ' ')
            comparisons[f'batter_{player_name}'] = pd.read_csv(file)
        
        # Load pitcher comparisons
        for file in pitcher_files:
            player_name = os.path.basename(file).replace('similar_pitchers_to_', '').replace('.csv', '').replace('_', ' ')
            comparisons[f'pitcher_{player_name}'] = pd.read_csv(file)
        
        logger.info("Loaded player comparisons successfully")
        return comparisons
    
    except Exception as e:
        logger.error(f"Error loading player comparisons: {e}")
        return comparisons

# Load all data
batting_data = load_data('batting')
pitching_data = load_data('pitching')
team_data = load_data('team')
statcast_data = load_data('statcast')
model_results = load_model_results()
player_comparisons = load_player_comparisons()

# Get available years
available_years = []
if not batting_data.empty and 'Season' in batting_data.columns:
    available_years = sorted(batting_data['Season'].unique().tolist())

# Initialize the Dash app
app = dash.Dash(__name__, suppress_callback_exceptions=True)
server = app.server

# Define the app layout
app.layout = html.Div([
    html.H1("Baseball Analytics Dashboard", style={'textAlign': 'center'}),
    
    dcc.Tabs([
        # Player Performance Tab
        dcc.Tab(label='Player Performance', children=[
            html.Div([
                html.H3("Player Performance Analysis"),
                
                html.Div([
                    html.Label("Select Year:"),
                    dcc.Dropdown(
                        id='player-year-dropdown',
                        options=[{'label': str(year), 'value': year} for year in available_years],
                        value=available_years[-1] if available_years else None,
                        style={'width': '200px'}
                    ),
                    
                    html.Label("Select Player Type:"),
                    dcc.RadioItems(
                        id='player-type-radio',
                        options=[
                            {'label': 'Batters', 'value': 'batting'},
                            {'label': 'Pitchers', 'value': 'pitching'}
                        ],
                        value='batting',
                        style={'margin': '10px 0'}
                    ),
                    
                    html.Label("Select Player:"),
                    dcc.Dropdown(
                        id='player-dropdown',
                        style={'width': '400px'}
                    ),
                ], style={'margin': '20px 0'}),
                
                html.Div([
                    html.Div([
                        dcc.Graph(id='player-performance-graph')
                    ], style={'width': '70%', 'display': 'inline-block', 'vertical-align': 'top'}),
                    
                    html.Div([
                        html.H4("Player Stats"),
                        html.Div(id='player-stats-table')
                    ], style={'width': '30%', 'display': 'inline-block', 'vertical-align': 'top'})
                ]),
                
                html.Div([
                    html.H4("Similar Players"),
                    html.Div(id='similar-players-table')
                ], style={'margin': '20px 0'})
            ])
        ]),
        
        # Team Analysis Tab
        dcc.Tab(label='Team Analysis', children=[
            html.Div([
                html.H3("Team Performance Analysis"),
                
                html.Div([
                    html.Label("Select Year:"),
                    dcc.Dropdown(
                        id='team-year-dropdown',
                        options=[{'label': str(year), 'value': year} for year in available_years],
                        value=available_years[-1] if available_years else None,
                        style={'width': '200px'}
                    ),
                    
                    html.Label("Select Metric:"),
                    dcc.Dropdown(
                        id='team-metric-dropdown',
                        options=[
                            {'label': 'Batting Average (AVG)', 'value': 'AVG'},
                            {'label': 'On-Base Percentage (OBP)', 'value': 'OBP'},
                            {'label': 'Slugging Percentage (SLG)', 'value': 'SLG'},
                            {'label': 'On-Base Plus Slugging (OPS)', 'value': 'OPS'},
                            {'label': 'Home Runs (HR)', 'value': 'HR'},
                            {'label': 'Runs Batted In (RBI)', 'value': 'RBI'},
                            {'label': 'Wins Above Replacement (WAR)', 'value': 'WAR'}
                        ],
                        value='OPS',
                        style={'width': '400px'}
                    )
                ], style={'margin': '20px 0'}),
                
                dcc.Graph(id='team-performance-graph'),
                
                html.Div([
                    html.H4("Team Rankings"),
                    html.Div(id='team-rankings-table')
                ], style={'margin': '20px 0'})
            ])
        ]),
        
        # Statcast Analysis Tab
        dcc.Tab(label='Statcast Analysis', children=[
            html.Div([
                html.H3("Statcast Data Analysis"),
                
                html.Div([
                    html.Label("Select Visualization:"),
                    dcc.Dropdown(
                        id='statcast-viz-dropdown',
                        options=[
                            {'label': 'Launch Angle vs. Launch Speed', 'value': 'launch'},
                            {'label': 'Pitch Type Distribution', 'value': 'pitch_type'},
                            {'label': 'Pitch Velocity Distribution', 'value': 'velocity'},
                            {'label': 'Spin Rate Analysis', 'value': 'spin_rate'}
                        ],
                        value='launch',
                        style={'width': '400px'}
                    )
                ], style={'margin': '20px 0'}),
                
                dcc.Graph(id='statcast-graph'),
                
                html.Div([
                    html.H4("Statcast Insights"),
                    html.Div(id='statcast-insights')
                ], style={'margin': '20px 0'})
            ])
        ]),
        
        # Predictive Models Tab
        dcc.Tab(label='Predictive Models', children=[
            html.Div([
                html.H3("Predictive Models Analysis"),
                
                html.Div([
                    html.Label("Select Model Type:"),
                    dcc.RadioItems(
                        id='model-type-radio',
                        options=[
                            {'label': 'Batting WAR Model', 'value': 'batting'},
                            {'label': 'Pitching WAR Model', 'value': 'pitching'}
                        ],
                        value='batting',
                        style={'margin': '10px 0'}
                    )
                ], style={'margin': '20px 0'}),
                
                html.Div([
                    html.Div([
                        dcc.Graph(id='feature-importance-graph')
                    ], style={'width': '70%', 'display': 'inline-block', 'vertical-align': 'top'}),
                    
                    html.Div([
                        html.H4("Model Metrics"),
                        html.Div(id='model-metrics-table')
                    ], style={'width': '30%', 'display': 'inline-block', 'vertical-align': 'top'})
                ]),
                
                html.H4("WAR Prediction Tool"),
                
                html.Div([
                    html.Div([
                        html.Label("Enter Player Stats:"),
                        html.Div(id='prediction-inputs'),
                        html.Button('Predict WAR', id='predict-button', n_clicks=0, 
                                   style={'margin': '20px 0', 'padding': '10px', 'background-color': '#4CAF50', 'color': 'white'})
                    ], style={'width': '60%', 'display': 'inline-block', 'vertical-align': 'top'}),
                    
                    html.Div([
                        html.H4("Prediction Result"),
                        html.Div(id='prediction-result')
                    ], style={'width': '40%', 'display': 'inline-block', 'vertical-align': 'top'})
                ])
            ])
        ])
    ])
])

# Callback to update player dropdown based on year and player type
@app.callback(
    Output('player-dropdown', 'options'),
    [Input('player-year-dropdown', 'value'),
     Input('player-type-radio', 'value')]
)
def update_player_dropdown(year, player_type):
    if year is None:
        return []
    
    if player_type == 'batting':
        data = batting_data
    else:
        data = pitching_data
    
    if data.empty or 'Season' not in data.columns or 'Name' not in data.columns:
        return []
    
    filtered_data = data[data['Season'] == year]
    players = sorted(filtered_data['Name'].unique())
    
    return [{'label': player, 'value': player} for player in players]

# Callback to update player dropdown value when options change
@app.callback(
    Output('player-dropdown', 'value'),
    [Input('player-dropdown', 'options')]
)
def set_player_value(available_options):
    if available_options and len(available_options) > 0:
        return available_options[0]['value']
    return None

# Callback to update player performance graph
@app.callback(
    Output('player-performance-graph', 'figure'),
    [Input('player-dropdown', 'value'),
     Input('player-year-dropdown', 'value'),
     Input('player-type-radio', 'value')]
)
def update_player_graph(player, year, player_type):
    if player is None or year is None:
        return go.Figure()
    
    if player_type == 'batting':
        data = batting_data
        # Define metrics to show for batters
        metrics = ['AVG', 'OBP', 'SLG', 'OPS', 'HR', 'RBI', 'BB%', 'K%']
    else:
        data = pitching_data
        # Define metrics to show for pitchers
        metrics = ['ERA', 'WHIP', 'K/9', 'BB/9', 'HR/9', 'FIP', 'xFIP']
    
    if data.empty or 'Season' not in data.columns or 'Name' not in data.columns:
        return go.Figure()
    
    # Filter data for the selected player
    player_data = data[(data['Name'] == player) & (data['Season'] == year)]
    
    if player_data.empty:
        return go.Figure()
    
    # Create a radar chart for the player's performance
    available_metrics = [m for m in metrics if m in player_data.columns]
    
    if not available_metrics:
        return go.Figure()
    
    # Get player values
    values = player_data[available_metrics].iloc[0].tolist()
    
    # Get league averages for comparison
    league_data = data[data['Season'] == year]
    league_avgs = league_data[available_metrics].mean().tolist()
    
    # Create radar chart
    fig = go.Figure()
    
    fig.add_trace(go.Scatterpolar(
        r=values,
        theta=available_metrics,
        fill='toself',
        name=player
    ))
    
    fig.add_trace(go.Scatterpolar(
        r=league_avgs,
        theta=available_metrics,
        fill='toself',
        name='League Average'
    ))

(Content truncated due to size limit. Use line ranges to read in chunks)