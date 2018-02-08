import React, { Component } from 'react';
import ReactDOM from 'react-dom';

export default class Example extends Component {
    render() {
        return (
            <div className="card">
                <div className="card-header">
                    Example Component
                </div>
                <div className="card-body">
                    <p className="card-text">I'm an example component!</p>
                </div>
            </div>
        );
    }
}

if (document.getElementById('example')) {
    ReactDOM.render(<Example />, document.getElementById('example'));
}
