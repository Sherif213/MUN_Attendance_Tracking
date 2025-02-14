import { useEffect, useState } from "react";
import {
  TextField,
  Button,
  Container,
  Typography,
  Grid,
  Card,
  CardContent,
  Divider,
} from "@mui/material";
import { CheckCircle, Cancel } from "@mui/icons-material";

const AttendanceForm = () => {
  const [formData, setFormData] = useState({ id: "", playcard: "", code: "" });
  const [errors, setErrors] = useState({});
  const [attendance, setAttendance] = useState({
    session1: false,
    session2: false,
    session3: false,
    session4: false,
    session5: false,
  });
  const [submitted, setSubmitted] = useState(false);
  const [dateTime, setDateTime] = useState(
    new Date().toLocaleString("en-GB", { timeZone: "Europe/Istanbul" })
  );

  useEffect(() => {
    const interval = setInterval(() => {
      setDateTime(
        new Date().toLocaleString("en-GB", { timeZone: "Europe/Istanbul" })
      );
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const validateForm = () => {
    let newErrors = {};
    if (!formData.id) newErrors.id = "ID is required";
    if (!formData.playcard) newErrors.playcard = "Placard is required";
    if (!formData.code) newErrors.code = "Code on Board is required";
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleCheckAttendance = async () => {
    if (!formData.id || !formData.playcard) {
      alert("Please enter ID and Placard to check attendance.");
      return;
    }

    try {
      const response = await fetch(
        "https://afaqenterprise.com/api/check_attendance.php",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id: formData.id,
            play_card: formData.playcard,
          }),
        }
      );
      // console.log("Request Payload:", {
      //   id: formData.id,
      //   play_card: formData.playcard,
      // });
      const result = await response.json();
      // console.log("Server Response:", result);
      if (result.success) {
        setAttendance(result.attendance);
      } else {
        alert("No attendance record found.");
      }
    } catch (error) {
      console.error("Error fetching attendance:", error);
      alert("Error connecting to server. Try again later.");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (validateForm()) {
      try {
        const response = await fetch(
          "https://afaqenterprise.com/api/submit_attendance.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData),
          }
        );
        const result = await response.json();
        if (result.success) {
          setAttendance(result.attendance);
          setSubmitted(true);
        } else {
          alert("Invalid ID or Placard. Please try again.");
        }
      } catch (error) {
        console.error("Error submitting form:", error);
        alert("Error connecting to server. Try again later.");
      }
    }
  };

  if (submitted) {
    return (
      <Container
        maxWidth="md"
        sx={{
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          mt: 5,
        }}
      >
        <Card
          sx={{
            p: 3,
            boxShadow: 3,
            borderRadius: 3,
            width: "100%",
            maxWidth: 600,
            bgcolor: "#ffffff",
          }}
        >
          <CardContent>
            <Typography
              variant="h4"
              align="center"
              sx={{ fontWeight: "bold", color: "#1976D2" }}
            >
              Thank You for Submitting!
            </Typography>
            <Typography variant="h6" align="center" sx={{ color: "#555" }}>
              Your attendance has been recorded.
            </Typography>
          </CardContent>
        </Card>
      </Container>
    );
  }

  return (
    <Container
      maxWidth="md"
      sx={{
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        mt: 5,
      }}
    >
      <Card
        sx={{
          p: 3,
          boxShadow: 3,
          borderRadius: 3,
          width: "100%",
          maxWidth: 600,
          bgcolor: "#ffffff",
        }}
      >
        <CardContent>
          <Typography
            variant="h6"
            gutterBottom
            align="center"
            sx={{ fontWeight: "bold", color: "#555" }}
          >
            {dateTime}
          </Typography>
          <Typography
            variant="h4"
            gutterBottom
            align="center"
            sx={{ fontWeight: "bold", color: "#1976D2" }}
          >
            Attendance Form
          </Typography>
          <Divider sx={{ my: 3 }} />
          <Grid container spacing={2} justifyContent="center">
            {Object.keys(attendance).map((session, index) => (
              <Grid
                item
                xs={4}
                key={index}
                sx={{
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <Typography
                  sx={{
                    display: "flex",
                    alignItems: "center",
                    fontWeight: "bold",
                  }}
                >
                  {session.replace("session", "Session ")}:
                  {attendance[session] ? (
                    <CheckCircle color="success" sx={{ ml: 1 }} />
                  ) : (
                    <Cancel color="error" sx={{ ml: 1 }} />
                  )}
                </Typography>
              </Grid>
            ))}
          </Grid>
          <form onSubmit={handleSubmit} style={{ marginTop: 20 }}>
            <TextField
              fullWidth
              label="ID"
              name="id"
              value={formData.id}
              onChange={handleChange}
              error={!!errors.id}
              helperText={errors.id}
              margin="normal"
              variant="outlined"
            />
            <TextField
              fullWidth
              label="Placard"
              name="playcard"
              value={formData.playcard}
              onChange={handleChange}
              error={!!errors.playcard}
              helperText={errors.playcard}
              margin="normal"
              variant="outlined"
            />
            <TextField
              fullWidth
              label="Code on Board"
              name="code"
              value={formData.code}
              onChange={handleChange}
              error={!!errors.code}
              helperText={errors.code}
              margin="normal"
              variant="outlined"
            />
            <Button
              type="submit"
              variant="contained"
              color="primary"
              fullWidth
              sx={{ mt: 2, py: 1.5, fontSize: "1rem" }}
            >
              Submit Attendance
            </Button>
          </form>
          <Button
            onClick={handleCheckAttendance}
            variant="outlined"
            color="secondary"
            fullWidth
            sx={{ mt: 2, py: 1.5, fontSize: "1rem" }}
          >
            Check Attendance
          </Button>
        </CardContent>
      </Card>
    </Container>
  );
};

export default AttendanceForm;
