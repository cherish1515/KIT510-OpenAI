# This app.py is used to run the flask server to send step_description and receive the answer from OpenAI 

from flask import Flask, request, jsonify
from flask_cors import CORS
import openai

app = Flask(__name__)
CORS(app)

# Set your OpenAI API key here
openai.api_key = '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'

@app.route('/process_data', methods=['POST'])
def process_data():
    if request.form:
        # Handle individual form submission
        step_description = request.form['step_description']
        step_id = request.form['step_id']

        # Use OpenAI API to generate a response for generated_answer
        response_generated = openai.Completion.create(
            engine="text-davinci-003",
            prompt=step_description,
            max_tokens=150  # Set the desired length of the response
        )

        # Extract the generated answer from the OpenAI response
        generated_answer = response_generated.choices[0].text.strip()

        # Assuming solution_description is obtained from OpenAI API
        solution_description = "Solution description for step {}".format(step_id)

        # Return the generated answers and solution_description as a dictionary
        return jsonify({'generated_answer': generated_answer, 'solution_description': solution_description, 'step_id': step_id})

    else:
        # Handle bulk form submission (JSON data)
        data = request.get_json()

        responses = {}  # Dictionary to store responses
        for step_data in data:
            step_id = step_data['step_id']
            step_description = step_data['step_description']

            # Use OpenAI API to generate a response
            response_generated = openai.Completion.create(
                engine="text-davinci-003",
                prompt=step_description,
                max_tokens=150  # Set the desired length of the response
            )

            # Extract the generated answers from the OpenAI responses
            generated_answer = response_generated.choices[0].text.strip()

            # Assuming solution_description is obtained from OpenAI API
            solution_description = "Solution description for step {}".format(step_id)

            responses[step_id] = generated_answer  # Store the generated answer

        # Return the responses as JSON
        return jsonify(responses)

if __name__ == '__main__':
    app.run(debug=True)
