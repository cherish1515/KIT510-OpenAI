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

        # Return the generated answers and solution_description as a dictionary
        return jsonify({'generated_answer': generated_answer, 'step_id': step_id})

    else:
        # Handle bulk form submission (JSON data)
        data = request.get_json()

        responses = {}  # Dictionary to store responses
        for step_data in data:
            step_id = step_data['step_id']
            step_description = step_data['step_description']
            student_answer = step_data['student_answer']

            # Use OpenAI API to generate a response
            response_generated = openai.Completion.create(
                engine="text-davinci-003",
                prompt=f"Step description: {step_description}\nStudent answer: {student_answer}\n",
                max_tokens=300  # Set the desired length of the response
            )

            # Extract the generated answers from the OpenAI responses
            generated_answer = response_generated.choices[0].text.strip()

            responses[step_id] = generated_answer  # Store the generated answer

        # Return the responses as JSON
        return jsonify(responses)

if __name__ == '__main__':
    app.run(debug=True)
